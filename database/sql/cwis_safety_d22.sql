-- Isolated D2.2 classification: the legacy helper and the other 14 formulas are not replaced.
CREATE OR REPLACE FUNCTION cwis.safety_d22_system_safe(
    system_id integer, type_id integer, sewer varchar, sewer_tp integer,
    drain varchar, drain_tp integer, cover varchar, surface varchar)
RETURNS boolean LANGUAGE sql IMMUTABLE AS $$
    SELECT COALESCE(CASE
        WHEN system_id = 1 THEN sewer IS NOT NULL AND sewer_tp IS NOT NULL
        WHEN system_id = 2 THEN drain IS NOT NULL AND drain_tp IS NOT NULL AND lower(cover)='closed' AND lower(surface)='lined'
        WHEN system_id IN (3,11) THEN type_id=3
            OR (type_id=1 AND sewer IS NOT NULL AND sewer_tp IS NOT NULL)
            OR (type_id=2 AND drain IS NOT NULL AND drain_tp IS NOT NULL AND lower(cover)='closed' AND lower(surface)='lined')
        WHEN system_id=4 THEN type_id IN (8,10)
            OR (type_id=13 AND sewer IS NOT NULL AND sewer_tp IS NOT NULL)
            OR (type_id=14 AND drain IS NOT NULL AND drain_tp IS NOT NULL AND lower(cover)='closed' AND lower(surface)='lined')
        WHEN system_id IN (5,6) THEN true ELSE false END, false);
$$;

CREATE OR REPLACE FUNCTION cwis.safety_buildings_d22(_year integer)
RETURNS TABLE(bin varchar, households numeric, private_households numeric, population numeric,
    private_population numeric, safe boolean, system_id integer, containment_type_id integer,
    lic_id integer, functional_use_id integer, use_category_id integer)
LANGUAGE sql STABLE AS $$
    WITH classified AS (
        SELECT h.bin, bool_and(CASE WHEN h.sanitation_system_id=9 THEN
            h.toilet_id IS NOT NULL AND cwis.safety_d22_system_safe(h.ct_sanitation_system_id,h.ct_containment_type_id,
                h.sewer_code,h.sewer_connected_to_tp,h.drain_code,h.drain_connected_to_tp,h.drain_cover_type,h.drain_surface_type)
            ELSE cwis.safety_d22_system_safe(h.sanitation_system_id,h.containment_type_id,
                h.sewer_code,h.sewer_connected_to_tp,h.drain_code,h.drain_connected_to_tp,h.drain_cover_type,h.drain_surface_type) END) AS safe
        FROM public.execute_select_build_sanisys_nd_criterias() h GROUP BY h.bin
    )
    SELECT b.bin,b.household_served::numeric,b.household_with_private_toilet::numeric,
        b.population_served::numeric,b.population_with_private_toilet::numeric,COALESCE(c.safe,false),
        b.sanitation_system_id,NULL::integer,b.lic_id,b.functional_use_id,b.use_category_id
    FROM building_info.buildings b LEFT JOIN classified c ON c.bin=b.bin
    WHERE b.deleted_at IS NULL AND b.construction_year < make_date(_year+1,1,1);
$$;

CREATE OR REPLACE FUNCTION cwis.safety_d22_option(_year integer, _name text)
RETURNS text LANGUAGE sql STABLE AS $$
    SELECT COALESCE(
        (SELECT source_snapshot->'formula_options'->>_name FROM cwis.safety_generation_runs
            WHERE year=_year AND country_code=cwis.generation_country()),
        (SELECT value FROM public.site_settings WHERE category='cwis_setting' AND name=_name AND deleted_at IS NULL ORDER BY id DESC LIMIT 1),
        'pending');
$$;

CREATE OR REPLACE FUNCTION cwis.prepare_safety_d22_generation(_year integer)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE country varchar:=cwis.generation_country(); source_id bigint; snapshot jsonb;
BEGIN
    PERFORM pg_advisory_xact_lock(73921,_year);
    IF EXISTS (SELECT 1 FROM cwis.generation_runs WHERE year=_year AND country_code<>country) THEN
        RAISE EXCEPTION 'Existing CWIS results belong to another country';
    END IF;
    IF EXISTS (SELECT 1 FROM cwis.indicators_for_country(country) WHERE indicator_code IN
        ('SF-3d','SF-4','SF-4c','SF-11a','SF-11a1','SF-11b','SF-11b1','SF-11c','SF-12')) THEN
        SELECT id INTO source_id FROM cwis.safety_submissions WHERE reporting_year=_year AND country_code=country
            AND status='approved' AND approved_at IS NOT NULL;
        IF source_id IS NULL THEN RAISE EXCEPTION 'Approve Safety indicator data for % (%) before generating CWIS results',_year,country; END IF;
    END IF;
    SELECT jsonb_build_object(
        'answers', COALESCE((SELECT jsonb_agg(to_jsonb(a)) FROM cwis.safety_answers a WHERE submission_id=source_id),'[]'::jsonb),
        'waiting_times', COALESCE((SELECT jsonb_agg(to_jsonb(w)||jsonb_build_object('toilet_type',t.type,'operational',t.status))
            FROM cwis.safety_waiting_times w JOIN fsm.toilets t ON t.id=w.toilet_id
            WHERE w.submission_id=source_id AND t.deleted_at IS NULL),'[]'::jsonb),
        'public_spaces', COALESCE((SELECT jsonb_agg(to_jsonb(p)||jsonb_build_object('toilet_type',t.type,'operational',t.status))
            FROM cwis.safety_public_spaces p LEFT JOIN fsm.toilets t ON t.id=p.toilet_id AND t.deleted_at IS NULL
            WHERE p.submission_id=source_id),'[]'::jsonb),
        'inventory_complete', COALESCE((SELECT public_spaces_complete FROM cwis.safety_submissions WHERE id=source_id),false),
        'formula_options', COALESCE((SELECT jsonb_object_agg(name,value) FROM public.site_settings
            WHERE category='cwis_setting' AND name IN ('safety_sf1_household_basis','safety_sf1e_volume_basis','safety_waiting_method','safety_sf6f_scope') AND deleted_at IS NULL),'{}'::jsonb)
    ) INTO snapshot;
    INSERT INTO cwis.safety_generation_runs(year,country_code,submission_id,source_snapshot,generated_at)
        VALUES (_year,country,source_id,snapshot,now()) ON CONFLICT (year) DO UPDATE SET
        country_code=EXCLUDED.country_code,submission_id=EXCLUDED.submission_id,source_snapshot=EXCLUDED.source_snapshot,generated_at=now();
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_safety_d22(_year integer, _code varchar)
RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE a numeric; b numeric; result numeric; mode text; target_names text[]; snapshot jsonb;
    item jsonb; parent jsonb; parent_code text; waiting_type text;
BEGIN
    IF _year IS NULL OR _year NOT BETWEEN 2 AND 9998 THEN RAISE EXCEPTION 'Invalid Safety reporting year'; END IF;
    IF _code IN ('SF-1b','SF-1c','SF-1d','SF-1f') THEN
        RETURN cwis.calculate_safety_revised(_year,_code)::text;
    END IF;
    SELECT source_snapshot INTO snapshot FROM cwis.safety_generation_runs
        WHERE year=_year AND country_code=cwis.generation_country();
    CASE _code
    WHEN 'SF-1' THEN
        mode:=cwis.safety_d22_option(_year,'safety_sf1_household_basis');
        IF mode NOT IN ('private','served') THEN RETURN NULL; END IF;
        SELECT CASE WHEN count(*) FILTER (WHERE safe AND (CASE WHEN mode='private' THEN private_households ELSE households END IS NULL
                OR CASE WHEN mode='private' THEN private_households ELSE households END<0))>0 THEN NULL
            ELSE sum(CASE WHEN mode='private' THEN private_households ELSE households END) FILTER (WHERE safe) END,
            CASE WHEN count(*) FILTER (WHERE households IS NULL OR households<0)>0 THEN NULL ELSE sum(households) END
        INTO a,b FROM cwis.safety_buildings_d22(_year);
        IF a IS NULL AND NOT EXISTS (SELECT 1 FROM cwis.safety_buildings_d22(_year) WHERE safe) THEN a:=0; END IF;
    WHEN 'SF-1a' THEN
        SELECT CASE WHEN count(*) FILTER (WHERE safe AND (private_population IS NULL OR private_population<0))>0 THEN NULL
                ELSE COALESCE(sum(private_population) FILTER (WHERE safe),0) END,
            CASE WHEN count(*) FILTER (WHERE population IS NULL OR population<0)>0 THEN NULL ELSE sum(population) END
        INTO a,b FROM cwis.safety_buildings_d22(_year);
    WHEN 'SF-1e' THEN
        mode:=cwis.safety_d22_option(_year,'safety_sf1e_volume_basis');
        IF mode NOT IN ('transaction','daily_rate') THEN RETURN NULL; END IF;
        SELECT 365*sum(capacity_per_day) INTO a FROM fsm.treatment_plants
            WHERE deleted_at IS NULL AND status=true AND type IN (3,4);
        SELECT sum(volume_of_sludge)*CASE WHEN mode='daily_rate' THEN 365 ELSE 1 END INTO b
            FROM fsm.sludge_collections WHERE deleted_at IS NULL
            AND date>=make_date(_year-1,1,1) AND date<make_date(_year,1,1);
    WHEN 'SF-1g' THEN
        SELECT count(*) FILTER (WHERE treatment_plant_id IS NOT NULL AND bod<cwis.safety_setting_numeric('bod_compliance_threshold_mg_l',0.000001,1000000)),count(*)
        INTO a,b FROM fsm.treatmentplant_tests WHERE deleted_at IS NULL AND date>=make_date(_year,1,1) AND date<make_date(_year+1,1,1);
    WHEN 'SF-2' THEN
        SELECT CASE WHEN count(*) FILTER (WHERE safe AND (households IS NULL OR households<0))>0 THEN NULL ELSE COALESCE(sum(households) FILTER (WHERE safe),0) END,
            CASE WHEN count(*) FILTER (WHERE households IS NULL OR households<0)>0 THEN NULL ELSE sum(households) END
        INTO a,b FROM cwis.safety_buildings_d22(_year) WHERE lic_id IS NOT NULL;
    WHEN 'SF-3a' THEN
        SELECT count(*) FILTER (WHERE safe),count(*) INTO a,b FROM cwis.safety_buildings_d22(_year)
            WHERE functional_use_id=8 AND use_category_id=34;
    WHEN 'SF-3d','SF-4c' THEN
        mode:=cwis.safety_d22_option(_year,'safety_waiting_method');
        IF mode NOT IN ('literal','weighted') OR snapshot IS NULL THEN RETURN NULL; END IF;
        waiting_type:=CASE WHEN _code='SF-3d' THEN 'community toilet' ELSE 'public toilet' END;
        IF EXISTS (SELECT 1 FROM jsonb_array_elements(snapshot->'waiting_times') w
            WHERE lower(w->>'toilet_type')=waiting_type AND (w->>'operational')::boolean
                AND ((w->>'waiting_minutes') IS NULL OR (w->>'number_users') IS NULL
                    OR (w->>'number_users')::numeric<=0 OR NULLIF(w->>'document_path','') IS NULL)) THEN RETURN NULL; END IF;
        SELECT CASE WHEN mode='literal' THEN sum((w->>'waiting_minutes')::numeric/NULLIF((w->>'number_users')::numeric,0))
            ELSE sum((w->>'waiting_minutes')::numeric*(w->>'number_users')::numeric)/NULLIF(sum((w->>'number_users')::numeric),0) END
        INTO result FROM jsonb_array_elements(snapshot->'waiting_times') w
            WHERE lower(w->>'toilet_type')=waiting_type AND (w->>'operational')::boolean;
        RETURN round(result,3)::text;
    WHEN 'SF-4' THEN
        IF snapshot IS NULL OR NOT COALESCE((snapshot->>'inventory_complete')::boolean,false) THEN RETURN NULL; END IF;
        SELECT count(*) FILTER (WHERE lower(p->>'toilet_type')='public toilet' AND (p->>'operational')::boolean),count(*) INTO a,b
        FROM jsonb_array_elements(snapshot->'public_spaces') p;
    WHEN 'SF-6a','SF-6b','SF-6c','SF-6d','SF-6e','SF-6f','SF-6g' THEN
        IF _code='SF-6f' THEN
            mode:=cwis.safety_d22_option(_year,'safety_sf6f_scope');
            IF mode NOT IN ('residential','residential_mixed') THEN RETURN NULL; END IF;
        END IF;
        target_names:=CASE _code WHEN 'SF-6a' THEN ARRAY['government institution'] WHEN 'SF-6b' THEN ARRAY['social institution']
            WHEN 'SF-6c' THEN ARRAY['financial institution'] WHEN 'SF-6d' THEN ARRAY['commercial','industrial']
            WHEN 'SF-6e' THEN ARRAY['cultural and religious'] WHEN 'SF-6g' THEN ARRAY['mixed (residential, commercial, office uses)']
            ELSE CASE WHEN mode='residential_mixed' THEN ARRAY['residential','mixed (residential, commercial, office uses)'] ELSE ARRAY['residential'] END END;
        SELECT count(*) FILTER (WHERE s.safe),count(*) INTO a,b FROM cwis.safety_buildings_d22(_year) s
            JOIN building_info.functional_uses f ON f.id=s.functional_use_id WHERE lower(f.name)=ANY(target_names);
    WHEN 'SF-8' THEN
        SELECT CASE WHEN count(*) FILTER (WHERE comply_with_maintainance_standards IS NULL)>0 THEN NULL
            ELSE count(*) FILTER (WHERE comply_with_maintainance_standards=true) END,count(*) INTO a,b
        FROM fsm.desludging_vehicles WHERE deleted_at IS NULL AND status=true;
    WHEN 'SF-10' THEN
        SELECT CASE WHEN count(*) FILTER (WHERE no_of_cases IS NULL OR no_of_cases<0)>0 THEN NULL ELSE sum(no_of_cases) END INTO a
        FROM public_health.waterborne_hotspots WHERE deleted_at IS NULL AND disease IN (1,2,3,6)
            AND date>=make_date(_year,1,1) AND date<make_date(_year+1,1,1);
        SELECT CASE WHEN count(*) FILTER (WHERE population IS NULL OR population<0)>0 THEN NULL ELSE sum(population) END INTO b
            FROM cwis.safety_buildings_d22(_year);
        RETURN CASE WHEN a>=0 AND b>0 THEN round(a/b*1000,3)::text END;
    WHEN 'SF-11a','SF-11a1','SF-11b','SF-11b1','SF-11c','SF-12' THEN
        IF snapshot IS NULL THEN RETURN NULL; END IF;
        parent_code:=CASE _code WHEN 'SF-11a1' THEN 'SF-11a' WHEN 'SF-11b1' THEN 'SF-11b' END;
        IF parent_code IS NOT NULL THEN
            SELECT x INTO parent FROM jsonb_array_elements(snapshot->'answers') x WHERE x->>'indicator_code'=parent_code;
            IF parent->>'answer'='No' THEN RETURN 'NA'; END IF;
            IF parent->>'answer' IS DISTINCT FROM 'Yes' THEN RETURN NULL; END IF;
        END IF;
        SELECT x INTO item FROM jsonb_array_elements(snapshot->'answers') x WHERE x->>'indicator_code'=_code;
        IF item->>'answer'='No' THEN RETURN 'No'; END IF;
        IF item->>'answer' IS DISTINCT FROM 'Yes' OR NULLIF(item->>'document_path','') IS NULL THEN RETURN NULL; END IF;
        IF _code='SF-12' AND NULLIF(item->>'biosolids_document_path','') IS NULL THEN RETURN NULL; END IF;
        RETURN 'Yes';
    ELSE RAISE EXCEPTION 'Unsupported D2.2 Safety indicator %',_code;
    END CASE;
    RETURN cwis.safety_percentage(a,b)::text;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.write_safety_d22(_year integer,_code varchar)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE country varchar:=cwis.generation_country(); result text;
BEGIN
    IF NOT cwis.indicator_applies_to_country(country,_code) THEN RETURN; END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.generation_runs WHERE year=_year AND country_code=country) THEN
        RAISE EXCEPTION 'Use the country-aware CWIS generation flow';
    END IF;
    result:=cwis.calculate_safety_d22(_year,_code);
    IF (SELECT count(*) FROM cwis.data_cwis WHERE year=_year AND indicator_code=_code AND deleted_at IS NULL)>1 THEN
        RAISE EXCEPTION 'Duplicate CWIS results for % / %',_year,_code;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.data_cwis WHERE year=_year AND indicator_code=_code AND deleted_at IS NULL) THEN
        INSERT INTO cwis.data_cwis(year,indicator_code,outcome,label,created_at)
        SELECT _year,indicator_code,outcome,label,now() FROM cwis.data_source WHERE indicator_code=_code;
    END IF;
    UPDATE cwis.data_cwis SET data_value=COALESCE(result,'NaN'),updated_at=now()
        WHERE year=_year AND indicator_code=_code AND deleted_at IS NULL;
END;
$$;

-- Keep individual named functions, including the legacy public signatures with extra parameters.
DO $$ DECLARE code text; suffix text; existing_args text; result_type text;
BEGIN
    FOREACH code IN ARRAY ARRAY['SF-1','SF-1a','SF-1b','SF-1c','SF-1d','SF-1e','SF-1f','SF-1g',
        'SF-2','SF-3a','SF-3d','SF-4','SF-4c','SF-6a','SF-6b','SF-6c','SF-6d','SF-6e','SF-6f','SF-6g',
        'SF-8','SF-10','SF-11a','SF-11a1','SF-11b','SF-11b1','SF-11c','SF-12'] LOOP
        suffix:=replace(lower(code),'sf-','');
        result_type:=CASE WHEN code IN ('SF-11a','SF-11a1','SF-11b','SF-11b1','SF-11c','SF-12') THEN 'text' ELSE 'numeric' END;
        EXECUTE format('CREATE OR REPLACE FUNCTION cwis.calculate_sf_%s(_year integer) RETURNS %s LANGUAGE sql STABLE AS %L',
            suffix,result_type,format('SELECT cwis.calculate_safety_d22(_year,%L)::%s',code,result_type));
        SELECT pg_get_function_arguments(p.oid) INTO existing_args FROM pg_proc p JOIN pg_namespace n ON n.oid=p.pronamespace
            WHERE n.nspname='public' AND p.proname='update_data_into_cwis_table_sf_'||suffix||'_newsan' LIMIT 1;
        EXECUTE format('CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_%s_newsan(%s) RETURNS void LANGUAGE plpgsql AS %L',
            suffix,COALESCE(existing_args,'_year integer'),format('BEGIN PERFORM cwis.write_safety_d22(_year,%L); END;',code));
    END LOOP;
END $$;

CREATE OR REPLACE FUNCTION cwis.dispatch_safety_d22(_year integer)
RETURNS void LANGUAGE plpgsql AS $$ DECLARE code text;
BEGIN
    FOREACH code IN ARRAY ARRAY['SF-2','SF-3a','SF-3d','SF-4','SF-4c','SF-6a','SF-6b','SF-6c','SF-6d','SF-6e','SF-6f','SF-6g',
        'SF-8','SF-10','SF-11a','SF-11a1','SF-11b','SF-11b1','SF-11c','SF-12'] LOOP
        PERFORM cwis.write_safety_d22(_year,code);
    END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION public.rebuild_cwis_safety(_year integer, _submission bigint)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE source cwis.safety_submissions%ROWTYPE; code text;
BEGIN
    PERFORM pg_advisory_xact_lock(73921,_year);
    SELECT * INTO source FROM cwis.safety_submissions WHERE id=_submission FOR UPDATE;
    IF NOT FOUND OR source.reporting_year<>_year OR source.country_code<>cwis.generation_country()
        OR source.status<>'approved' OR source.approved_at IS NULL THEN
        RAISE EXCEPTION 'An approved Safety submission for this year and country is required';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.data_cwis WHERE year=_year AND deleted_at IS NULL) THEN
        PERFORM public.generate_cwis_with_rpm(_year);
        RETURN;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM cwis.generation_runs WHERE year=_year AND country_code=source.country_code) THEN
        RAISE EXCEPTION 'Existing CWIS results belong to another country or lack a generation context';
    END IF;
    PERFORM cwis.prepare_safety_d22_generation(_year);
    PERFORM cwis.dispatch_safety_d22(_year);
    FOREACH code IN ARRAY ARRAY['SF-1','SF-1a','SF-1b','SF-1c','SF-1d','SF-1e','SF-1f','SF-1g'] LOOP
        PERFORM cwis.write_safety_d22(_year,code);
    END LOOP;
END;
$$;
