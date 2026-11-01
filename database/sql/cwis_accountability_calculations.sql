CREATE OR REPLACE FUNCTION cwis.accountability_function_map()
RETURNS TABLE(code varchar, suffix text) LANGUAGE sql IMMUTABLE AS $$
    VALUES ('AC-1a.1'::varchar,'1a_1'),
        ('AC-1a.2'::varchar,'1a_2'),
        ('AC-1a.3'::varchar,'1a_3'),
        ('AC-1a.4'::varchar,'1a_4'),
        ('AC-1a.5'::varchar,'1a_5'),
        ('AC-1a.6'::varchar,'1a_6'),
        ('AC-1a.7'::varchar,'1a_7'),
        ('AC-1a.8'::varchar,'1a_8'),
        ('AC-1a.9'::varchar,'1a_9'),
        ('AC-1a.10'::varchar,'1a_10'),
        ('AC-1b.1'::varchar,'1b_1'),
        ('AC-1b.2'::varchar,'1b_2'),
        ('AC-1b.3'::varchar,'1b_3'),
        ('AC-1b.4'::varchar,'1b_4'),
        ('AC-1b.5'::varchar,'1b_5'),
        ('AC-1b.6'::varchar,'1b_6'),
        ('AC-1b.7'::varchar,'1b_7'),
        ('AC-1b.8'::varchar,'1b_8'),
        ('AC-1b.9'::varchar,'1b_9'),
        ('AC-2.1'::varchar,'2_1'),
        ('AC-2.2'::varchar,'2_2'),
        ('AC-2.3'::varchar,'2_3'),
        ('AC-2.4'::varchar,'2_4'),
        ('AC-2.5'::varchar,'2_5'),
        ('AC-3a.1'::varchar,'3a_1'),
        ('AC-3b.1'::varchar,'3b_1'),
        ('AC-3b.2'::varchar,'3b_2'),
        ('AC-4a'::varchar,'4a'),
        ('AC-4b'::varchar,'4b');
$$;

CREATE OR REPLACE FUNCTION cwis.prepare_accountability_generation(_year integer)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE country varchar := cwis.generation_country(); source cwis.accountability_submissions%ROWTYPE; answers jsonb;
BEGIN
    IF _year IS NULL OR _year NOT BETWEEN 1 AND 9999 THEN RAISE EXCEPTION 'Invalid Accountability reporting year'; END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.indicators_for_country(country) WHERE outcome = 'accountability') THEN RETURN; END IF;
    PERFORM pg_advisory_xact_lock(73921, _year);
    SELECT * INTO source FROM cwis.accountability_submissions
    WHERE reporting_year = _year AND country_code = country AND status = 'approved' AND approved_at IS NOT NULL FOR SHARE;
    IF NOT FOUND THEN RAISE EXCEPTION 'Approve Accountability data for year % and country % before generation', _year, country; END IF;
    SELECT COALESCE(jsonb_object_agg(a.indicator_code, jsonb_build_object('answer', a.answer,
        'document_path', a.document_path, 'document_name', a.document_name)), '{}'::jsonb) INTO answers
    FROM cwis.accountability_answers a WHERE a.submission_id = source.id
        AND a.indicator_code IN (SELECT indicator_code FROM cwis.indicators_for_country(country) WHERE outcome = 'accountability');
    INSERT INTO cwis.accountability_generation_runs(year, country_code, submission_id, source_snapshot, generated_at)
    VALUES (_year, country, source.id, answers, now())
    ON CONFLICT (year) DO UPDATE SET country_code = EXCLUDED.country_code, submission_id = EXCLUDED.submission_id,
        source_snapshot = EXCLUDED.source_snapshot, generated_at = EXCLUDED.generated_at;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.accountability_context(_year integer)
RETURNS cwis.accountability_generation_runs LANGUAGE plpgsql STABLE AS $$
DECLARE context cwis.accountability_generation_runs%ROWTYPE;
BEGIN
    SELECT * INTO context FROM cwis.accountability_generation_runs WHERE year = _year;
    IF NOT FOUND OR context.country_code IS DISTINCT FROM cwis.generation_country() THEN
        RAISE EXCEPTION 'Accountability generation context missing or country mismatch';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.accountability_submissions s WHERE s.id = context.submission_id
        AND s.reporting_year = _year AND s.country_code = context.country_code AND s.status = 'approved' AND s.approved_at IS NOT NULL) THEN
        RAISE EXCEPTION 'Accountability approval required';
    END IF;
    RETURN context;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_accountability_answer(_year integer, _code varchar)
RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE context cwis.accountability_generation_runs%ROWTYPE; answer jsonb;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM cwis.accountability_function_map() WHERE code = _code)
        OR NOT cwis.indicator_applies_to_country(cwis.generation_country(), _code) THEN
        RAISE EXCEPTION 'Accountability indicator % is unavailable for the selected country', _code;
    END IF;
    context := cwis.accountability_context(_year);
    answer := context.source_snapshot->_code;
    IF answer->>'answer' = 'No' THEN RETURN 'No'; END IF;
    IF answer->>'answer' = 'Yes' AND NULLIF(btrim(answer->>'document_path'), '') IS NOT NULL THEN RETURN 'Yes'; END IF;
    RETURN NULL;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_1(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.1'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_2(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.2'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_3(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.3'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_4(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.4'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_5(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.5'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_6(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.6'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_7(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.7'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_8(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.8'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_9(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.9'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1a_10(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1a.10'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_1(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.1'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_2(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.2'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_3(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.3'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_4(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.4'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_5(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.5'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_6(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.6'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_7(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.7'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_8(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.8'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_1b_9(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-1b.9'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_2_1(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-2.1'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_2_2(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-2.2'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_2_3(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-2.3'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_2_4(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-2.4'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_2_5(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-2.5'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_3a_1(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-3a.1'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_3b_1(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-3b.1'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_3b_2(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-3b.2'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_4a(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-4a'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_ac_4b(_year integer) RETURNS text LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_accountability_answer(_year,'AC-4b'); $$;

CREATE OR REPLACE FUNCTION cwis.update_accountability_indicator(_year integer, _code varchar)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE context cwis.accountability_generation_runs%ROWTYPE; result text; calculator text; affected integer;
BEGIN
    PERFORM pg_advisory_xact_lock(73921, _year);
    IF NOT cwis.indicator_applies_to_country(cwis.generation_country(), _code) THEN RETURN; END IF;
    context := cwis.accountability_context(_year);
    IF NOT EXISTS (SELECT 1 FROM cwis.generation_runs WHERE year = _year AND country_code = context.country_code) THEN
        RAISE EXCEPTION 'CWIS result country does not match the Accountability source';
    END IF;
    SELECT 'calculate_ac_' || suffix INTO calculator FROM cwis.accountability_function_map() WHERE code = _code;
    IF calculator IS NULL THEN RAISE EXCEPTION 'Unsupported Accountability indicator %', _code; END IF;
    EXECUTE format('SELECT cwis.%I($1)', calculator) INTO result USING _year;
    SELECT count(*) INTO affected FROM cwis.data_cwis WHERE year = _year AND indicator_code = _code;
    IF affected > 1 THEN RAISE EXCEPTION 'Duplicate CWIS results for % / %', _year, _code; END IF;
    IF affected = 0 THEN
        INSERT INTO cwis.data_cwis(year, indicator_code, outcome, label, created_at)
        SELECT _year, indicator_code, outcome, label, now() FROM cwis.data_source WHERE indicator_code = _code AND outcome = 'accountability';
    END IF;
    UPDATE cwis.data_cwis SET data_value = result, updated_at = now() WHERE year = _year AND indicator_code = _code;
END;
$$;

CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_1_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.1'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_2_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.2'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_3_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.3'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_4_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.4'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_5_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.5'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_6_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.6'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_7_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.7'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_8_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.8'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_9_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.9'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1a_10_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1a.10'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_1_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.1'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_2_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.2'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_3_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.3'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_4_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.4'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_5_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.5'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_6_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.6'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_7_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.7'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_8_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.8'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_1b_9_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-1b.9'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_2_1_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-2.1'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_2_2_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-2.2'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_2_3_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-2.3'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_2_4_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-2.4'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_2_5_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-2.5'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_3a_1_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-3a.1'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_3b_1_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-3b.1'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_3b_2_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-3b.2'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_4a_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-4a'); END; $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_ac_4b_newsan(_year integer) RETURNS void LANGUAGE plpgsql AS $$ BEGIN PERFORM cwis.update_accountability_indicator(_year,'AC-4b'); END; $$;

CREATE OR REPLACE FUNCTION cwis.dispatch_accountability(_year integer)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE item record;
BEGIN
    FOR item IN SELECT * FROM cwis.accountability_function_map() LOOP
        IF cwis.indicator_applies_to_country(cwis.generation_country(), item.code) THEN
            EXECUTE format('SELECT public.%I($1)', 'update_data_into_cwis_table_ac_' || item.suffix || '_newsan') USING _year;
        END IF;
    END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION public.generate_cwis_with_accountability(_year integer)
RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    IF _year IS NULL OR _year NOT BETWEEN 1 AND 9999 THEN RAISE EXCEPTION 'A valid CWIS reporting year is required'; END IF;
    PERFORM pg_advisory_xact_lock(73921, _year);
    IF EXISTS (SELECT 1 FROM cwis.data_cwis WHERE year = _year) THEN RETURN; END IF;
    PERFORM cwis.prepare_accountability_generation(_year);
    PERFORM public.generate_cwis_with_responsibility(_year);
    -- The legacy insert creates all catalogue rows; retain only mapped AC rows.
    DELETE FROM cwis.data_cwis d WHERE d.year = _year AND d.outcome = 'accountability'
        AND NOT cwis.indicator_applies_to_country(cwis.generation_country(), d.indicator_code);
    PERFORM cwis.dispatch_accountability(_year);
END;
$$;

CREATE OR REPLACE FUNCTION public.rebuild_cwis_accountability(_year integer, _submission_id bigint)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE country varchar := cwis.generation_country();
BEGIN
    PERFORM pg_advisory_xact_lock(73921, _year);
    IF NOT EXISTS (SELECT 1 FROM cwis.indicators_for_country(country) WHERE outcome = 'accountability') THEN
        RAISE EXCEPTION 'Accountability is unavailable for the selected country';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.accountability_submissions WHERE id = _submission_id AND reporting_year = _year
        AND country_code = country AND status = 'approved' AND approved_at IS NOT NULL) THEN
        RAISE EXCEPTION 'Use the approved Accountability submission for this year and country';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.data_cwis WHERE year = _year) THEN
        PERFORM public.generate_cwis_with_accountability(_year);
        RETURN;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.generation_runs WHERE year = _year AND country_code = country) THEN
        RAISE EXCEPTION 'Existing CWIS results belong to another country; no results were replaced';
    END IF;
    PERFORM cwis.prepare_accountability_generation(_year);
    DELETE FROM cwis.data_cwis d WHERE d.year = _year AND d.outcome = 'accountability'
        AND NOT cwis.indicator_applies_to_country(country, d.indicator_code);
    PERFORM cwis.dispatch_accountability(_year);
END;
$$;
