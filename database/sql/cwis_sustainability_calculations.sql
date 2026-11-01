-- Annual financial inputs are already annualized; SS-2/SS-3 follow the written ratios.
CREATE OR REPLACE FUNCTION cwis.prepare_sustainability_generation(_year integer)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE
    country varchar := cwis.generation_country();
    source cwis.sustainability_submissions%ROWTYPE;
    finances jsonb;
    campaigns jsonb;
    plants jsonb;
BEGIN
    IF _year IS NULL OR _year NOT BETWEEN 1901 AND 9999 THEN RAISE EXCEPTION 'Invalid Sustainability reporting year'; END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.indicators_for_country(country) WHERE outcome = 'sustainability') THEN RETURN; END IF;
    PERFORM pg_advisory_xact_lock(73921, _year);
    IF EXISTS (SELECT 1 FROM cwis.indicators_for_country(country)
        WHERE outcome = 'sustainability' AND indicator_code NOT IN ('SS-1a','SS-1b')) THEN
        SELECT * INTO source FROM cwis.sustainability_submissions
        WHERE reporting_year = _year AND country_code = country AND status = 'approved' AND approved_at IS NOT NULL
        ORDER BY approved_at DESC, id DESC LIMIT 1 FOR SHARE;
        IF NOT FOUND THEN RAISE EXCEPTION 'Approve Sustainability data for year % and country % before generation', _year, country; END IF;
        SELECT to_jsonb(f) INTO finances FROM cwis.sustainability_financials f WHERE submission_id = source.id FOR SHARE;
        SELECT COALESCE(jsonb_object_agg(indicator_code, jsonb_build_object('answer', answer, 'document_path', document_path)), '{}'::jsonb)
        INTO campaigns FROM cwis.sustainability_campaigns WHERE submission_id = source.id;
    END IF;
    -- Snapshot both missing and available records so an incomplete city total is not silently reported.
    SELECT COALESCE(jsonb_agg(jsonb_build_object(
        'treatment_plant_id', p.id, 'type', p.type, 'status', p.status, 'source_id', a.id,
        'data_year', _year - 1, 'updated_at', a.updated_at,
        'treated_effluent_generated_volume', a.treated_effluent_generated_volume,
        'treated_effluent_reused_volume', a.treated_effluent_reused_volume,
        'treated_biosolids_generated_volume', a.treated_biosolids_generated_volume,
        'treated_biosolids_reused_volume', a.treated_biosolids_reused_volume
    ) ORDER BY p.id), '[]'::jsonb) INTO plants
    FROM fsm.treatment_plants p LEFT JOIN cwis.treatment_plant_annual_sustainability a
        ON a.treatment_plant_id = p.id AND a.data_year = _year - 1
    WHERE p.deleted_at IS NULL AND p.type::text IN ('1','2') AND lower(p.status::text) IN ('true','t','1');
    INSERT INTO cwis.sustainability_generation_runs(year, country_code, submission_id, source_data_year,
        source_snapshot, plant_snapshot, generated_at)
    VALUES (_year, country, source.id, _year - 1,
        jsonb_build_object('financials', finances, 'campaigns', COALESCE(campaigns, '{}'::jsonb), 'currency_code', source.currency_code), plants, now())
    ON CONFLICT (year) DO UPDATE SET country_code = EXCLUDED.country_code, submission_id = EXCLUDED.submission_id,
        source_data_year = EXCLUDED.source_data_year, source_snapshot = EXCLUDED.source_snapshot,
        plant_snapshot = EXCLUDED.plant_snapshot, generated_at = EXCLUDED.generated_at;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.sustainability_context(_year integer)
RETURNS cwis.sustainability_generation_runs LANGUAGE plpgsql STABLE AS $$
DECLARE context cwis.sustainability_generation_runs%ROWTYPE;
BEGIN
    SELECT * INTO context FROM cwis.sustainability_generation_runs WHERE year = _year;
    IF NOT FOUND OR context.country_code IS DISTINCT FROM cwis.generation_country() THEN
        RAISE EXCEPTION 'Sustainability generation context missing or country mismatch';
    END IF;
    IF context.submission_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM cwis.sustainability_submissions s WHERE s.id = context.submission_id
        AND s.reporting_year = _year AND s.country_code = context.country_code AND s.status = 'approved' AND s.approved_at IS NOT NULL
    ) THEN RAISE EXCEPTION 'Sustainability approval required'; END IF;
    RETURN context;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.sustainability_reuse_ratio(_year integer, _kind text)
RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE context cwis.sustainability_generation_runs%ROWTYPE; generated numeric; reused numeric; missing integer; total integer;
BEGIN
    context := cwis.sustainability_context(_year);
    IF _kind NOT IN ('effluent','biosolids') THEN RAISE EXCEPTION 'Invalid reuse source'; END IF;
    SELECT count(*), count(*) FILTER (WHERE item->>('treated_' || _kind || '_generated_volume') IS NULL
        OR item->>('treated_' || _kind || '_reused_volume') IS NULL),
        sum((item->>('treated_' || _kind || '_generated_volume'))::numeric),
        sum((item->>('treated_' || _kind || '_reused_volume'))::numeric)
    INTO total, missing, generated, reused FROM jsonb_array_elements(context.plant_snapshot) item;
    IF total = 0 OR missing > 0 THEN RETURN NULL; END IF;
    RETURN round(reused / NULLIF(generated, 0) * 100, 3);
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_ss_1a(_year integer) RETURNS numeric LANGUAGE sql STABLE AS $$
    SELECT cwis.sustainability_reuse_ratio(_year, 'effluent');
$$;
CREATE OR REPLACE FUNCTION cwis.calculate_ss_1b(_year integer) RETURNS numeric LANGUAGE sql STABLE AS $$
    SELECT cwis.sustainability_reuse_ratio(_year, 'biosolids');
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_ss_2(_year integer)
RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE context cwis.sustainability_generation_runs%ROWTYPE; f jsonb; a numeric; b numeric; c numeric; d numeric; e numeric;
BEGIN
    context := cwis.sustainability_context(_year); f := context.source_snapshot->'financials';
    a := (f->>'ct_pt_user_fee_revenue')::numeric; b := (f->>'ct_pt_om_expenditure')::numeric;
    c := (f->>'fsm_chain_revenue')::numeric; d := (f->>'sewerage_charges_revenue')::numeric;
    e := (f->>'sanitation_chain_om_expenditure')::numeric;
    RETURN round((a / NULLIF(b,0)) / NULLIF((c+d) / NULLIF(e,0),0), 3);
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_ss_3(_year integer)
RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE context cwis.sustainability_generation_runs%ROWTYPE; f jsonb;
BEGIN
    context := cwis.sustainability_context(_year); f := context.source_snapshot->'financials';
    RETURN round(((f->>'gov_funded_treatment_capex')::numeric + (f->>'gov_funded_ct_pt_capex')::numeric + (f->>'gov_funded_desludging_capex')::numeric)
        / NULLIF((f->>'annualized_treatment_capex')::numeric + (f->>'annualized_ct_pt_capex')::numeric + (f->>'annualized_desludging_capex')::numeric,0),3);
END;
$$;

CREATE OR REPLACE FUNCTION cwis.sustainability_campaign_result(_year integer, _code varchar)
RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE context cwis.sustainability_generation_runs%ROWTYPE; campaign jsonb;
BEGIN
    context := cwis.sustainability_context(_year); campaign := context.source_snapshot->'campaigns'->_code;
    IF campaign->>'answer' = 'No' THEN RETURN 'No'; END IF;
    IF campaign->>'answer' = 'Yes' AND COALESCE(campaign->>'document_path','') <> '' THEN RETURN 'Yes'; END IF;
    RETURN NULL;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_ss_4_1(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.sustainability_campaign_result(_year,'SS-4.1'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ss_4_1a(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.sustainability_campaign_result(_year,'SS-4.1a'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ss_4_1b(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.sustainability_campaign_result(_year,'SS-4.1b'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ss_4_1c(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.sustainability_campaign_result(_year,'SS-4.1c'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ss_4_1d(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.sustainability_campaign_result(_year,'SS-4.1d'); $$;

CREATE OR REPLACE FUNCTION cwis.update_sustainability_indicator(_year integer, _code varchar)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE context cwis.sustainability_generation_runs%ROWTYPE; result text; calculator text; affected integer;
BEGIN
    IF NOT cwis.indicator_applies_to_country(cwis.generation_country(), _code) THEN RETURN; END IF;
    context := cwis.sustainability_context(_year);
    IF NOT EXISTS (SELECT 1 FROM cwis.generation_runs WHERE year = _year AND country_code = context.country_code) THEN
        RAISE EXCEPTION 'CWIS result country does not match the Sustainability source';
    END IF;
    calculator := CASE _code WHEN 'SS-1a' THEN 'calculate_ss_1a' WHEN 'SS-1b' THEN 'calculate_ss_1b'
        WHEN 'SS-2' THEN 'calculate_ss_2' WHEN 'SS-3' THEN 'calculate_ss_3'
        WHEN 'SS-4.1' THEN 'calculate_ss_4_1' WHEN 'SS-4.1a' THEN 'calculate_ss_4_1a'
        WHEN 'SS-4.1b' THEN 'calculate_ss_4_1b' WHEN 'SS-4.1c' THEN 'calculate_ss_4_1c'
        WHEN 'SS-4.1d' THEN 'calculate_ss_4_1d' END;
    IF calculator IS NULL THEN RAISE EXCEPTION 'Unsupported Sustainability indicator %', _code; END IF;
    EXECUTE format('SELECT cwis.%I($1)::text', calculator) INTO result USING _year;
    SELECT count(*) INTO affected FROM cwis.data_cwis WHERE year = _year AND indicator_code = _code;
    IF affected > 1 THEN RAISE EXCEPTION 'Duplicate CWIS results for % / %', _year, _code; END IF;
    IF affected = 0 THEN
        INSERT INTO cwis.data_cwis(year, indicator_code, outcome, label, created_at)
        SELECT _year, indicator_code, outcome, label, now() FROM cwis.data_source WHERE indicator_code = _code;
    END IF;
    UPDATE cwis.data_cwis SET data_value = result, updated_at = now() WHERE year = _year AND indicator_code = _code;
END;
$$;

CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_1a_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-1a'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_1b_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-1b'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_2_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-2'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_3_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-3'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_4_1_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-4.1'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_4_1a_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-4.1a'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_4_1b_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-4.1b'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_4_1c_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-4.1c'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ss_4_1d_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_sustainability_indicator(_year,'SS-4.1d'); END; $$;

CREATE OR REPLACE FUNCTION cwis.dispatch_sustainability(_year integer)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE item record;
BEGIN
    FOR item IN SELECT * FROM (VALUES ('1a','SS-1a'),('1b','SS-1b'),('2','SS-2'),('3','SS-3'),
        ('4_1','SS-4.1'),('4_1a','SS-4.1a'),('4_1b','SS-4.1b'),('4_1c','SS-4.1c'),('4_1d','SS-4.1d')) d(suffix, code) LOOP
        IF cwis.indicator_applies_to_country(cwis.generation_country(), item.code) THEN
            EXECUTE format('SELECT public.%I($1)', 'update_data_into_cwis_table_ss_' || item.suffix || '_newsan') USING _year;
        END IF;
    END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION public.generate_cwis_with_sustainability(_year integer)
RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    IF _year IS NULL OR _year NOT BETWEEN 1 AND 9999 THEN RAISE EXCEPTION 'A valid CWIS reporting year is required'; END IF;
    PERFORM pg_advisory_xact_lock(73921, _year);
    IF EXISTS (SELECT 1 FROM cwis.data_cwis WHERE year = _year) THEN RETURN; END IF;
    PERFORM cwis.prepare_sustainability_generation(_year);
    PERFORM public.generate_cwis_with_equity(_year);
    PERFORM cwis.dispatch_sustainability(_year);
END;
$$;

CREATE OR REPLACE FUNCTION public.rebuild_cwis_sustainability(_year integer, _submission_id bigint)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE country varchar := cwis.generation_country();
BEGIN
    PERFORM pg_advisory_xact_lock(73921, _year);
    IF NOT EXISTS (SELECT 1 FROM cwis.sustainability_submissions WHERE id = _submission_id AND reporting_year = _year
        AND country_code = country AND status = 'approved' AND approved_at IS NOT NULL) THEN
        RAISE EXCEPTION 'Use the approved Sustainability submission for this year and country';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.data_cwis WHERE year = _year) THEN
        PERFORM public.generate_cwis_with_sustainability(_year);
        RETURN;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.generation_runs WHERE year = _year AND country_code = country) THEN
        RAISE EXCEPTION 'Existing CWIS results belong to another country; no results were replaced';
    END IF;
    PERFORM cwis.prepare_sustainability_generation(_year);
    PERFORM cwis.dispatch_sustainability(_year);
END;
$$;
