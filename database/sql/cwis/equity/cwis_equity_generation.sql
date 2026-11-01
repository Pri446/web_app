-- Existing database generation functions captured for reproducible installation.
CREATE OR REPLACE FUNCTION cwis.eq_2_population_totals(_year integer)
 RETURNS TABLE(a numeric, b numeric, c numeric, d numeric)
 LANGUAGE sql
 STABLE
AS $function$
    WITH buildings AS (
        -- Related sanitation records can repeat a building; count its population once.
        SELECT bin, MAX(population_served)::numeric AS population,
            BOOL_OR(lic_id IS NOT NULL) AS in_lic,
            BOOL_OR(LOWER(safely_managed_sanitation_system) = 'yes') AS safely_managed
        FROM public.execute_select_build_sanisys_nd_criterias()
        WHERE bin IS NOT NULL AND EXTRACT(YEAR FROM construction_year) <= _year
        GROUP BY bin
    )
    SELECT COALESCE(SUM(population) FILTER (WHERE in_lic AND safely_managed), 0),
        COALESCE(SUM(population) FILTER (WHERE in_lic), 0),
        COALESCE(SUM(population) FILTER (WHERE safely_managed), 0), SUM(population)
    FROM buildings;
$function$;


CREATE OR REPLACE FUNCTION cwis.generation_country()
 RETURNS character varying
 LANGUAGE plpgsql
 STABLE
AS $function$
DECLARE
    country varchar;
BEGIN
    SELECT value INTO country FROM public.site_settings
    WHERE name = 'active_country' AND category = 'cwis_setting' AND deleted_at IS NULL
    ORDER BY id LIMIT 1;
    country := COALESCE(country, 'ALL');
    IF country NOT IN ('ALL', 'NP', 'BD') THEN
        RAISE EXCEPTION 'Invalid CWIS country setting';
    END IF;
    RETURN country;
END;
$function$;


CREATE OR REPLACE FUNCTION cwis.update_equity_indicator(_year integer, _code character varying)
 RETURNS void
 LANGUAGE plpgsql
AS $function$
DECLARE
    context cwis.generation_runs%ROWTYPE;
    country varchar := cwis.generation_country();
    totals record;
    result text;
    calculator text;
    affected integer;
BEGIN
    IF NOT cwis.indicator_applies_to_country(country, _code) THEN RETURN; END IF;
    SELECT * INTO context FROM cwis.generation_runs WHERE year = _year FOR UPDATE;
    IF NOT FOUND OR context.country_code IS DISTINCT FROM country THEN
        RAISE EXCEPTION 'CWIS generation context missing or country mismatch for year %', _year;
    END IF;
    IF _code = 'EQ-2' THEN
        SELECT * INTO totals FROM cwis.eq_2_population_totals(_year);
        result := cwis.calculate_eq_2(totals.a, totals.b, totals.c, totals.d)::text;
    ELSE
        calculator := CASE _code
            WHEN 'EQ-3' THEN 'calculate_eq_3' WHEN 'EQ-4' THEN 'calculate_eq_4'
            WHEN 'EQ-4a' THEN 'calculate_eq_4a' WHEN 'EQ-5' THEN 'calculate_eq_5'
            WHEN 'EQ-6.1' THEN 'calculate_eq_6_1' WHEN 'EQ-6.1a' THEN 'calculate_eq_6_1a'
            WHEN 'EQ-6.1b' THEN 'calculate_eq_6_1b' WHEN 'EQ-6.2' THEN 'calculate_eq_6_2'
            WHEN 'EQ-6.3' THEN 'calculate_eq_6_3' WHEN 'EQ-6.3a' THEN 'calculate_eq_6_3a'
            WHEN 'EQ-6.3b' THEN 'calculate_eq_6_3b' WHEN 'EQ-6.4' THEN 'calculate_eq_6_4'
            WHEN 'EQ-6.5' THEN 'calculate_eq_6_5' END;
        IF calculator IS NULL THEN RAISE EXCEPTION 'Unsupported Equity indicator %', _code; END IF;
        IF NOT EXISTS (SELECT 1 FROM cwis.equity_submissions s
            WHERE s.id = context.equity_submission_id AND s.reporting_year = _year
                AND s.country_code = country AND s.status = 'approved'
                AND s.approved_at IS NOT NULL AND s.deleted_at IS NULL) THEN
            RAISE EXCEPTION 'CWIS approval required: approve Equity data for year % and country % first', _year, country;
        END IF;
        EXECUTE format('SELECT cwis.%I($1, $2)::text', calculator)
            INTO result USING context.equity_submission_id, country;
    END IF;
    UPDATE cwis.data_cwis SET data_value = result, updated_at = NOW()
    WHERE year = _year AND indicator_code = _code;
    GET DIAGNOSTICS affected = ROW_COUNT;
    IF affected <> 1 THEN
        RAISE EXCEPTION 'Expected one CWIS result row for % / %, found %', _year, _code, affected;
    END IF;
END;
$function$;


CREATE OR REPLACE FUNCTION public.generate_cwis_with_equity(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$
DECLARE
    country varchar := cwis.generation_country();
    approved_id bigint;
    item record;
BEGIN
    IF _year IS NULL OR _year NOT BETWEEN 1 AND 9999 THEN
        RAISE EXCEPTION 'A valid CWIS reporting year is required';
    END IF;
    PERFORM pg_advisory_xact_lock(73921, _year);
    -- Retain the classic no-regeneration rule for an existing annual snapshot.
    IF EXISTS (SELECT 1 FROM cwis.data_cwis WHERE year = _year) THEN
        RAISE NOTICE 'Data for year % already exists in data_cwis table', _year;
        RETURN;
    END IF;
    IF EXISTS (SELECT 1 FROM cwis.indicators_for_country(country)
        WHERE outcome = 'equity' AND indicator_code NOT IN ('EQ-1', 'EQ-2')) THEN
        SELECT id INTO approved_id FROM cwis.equity_submissions
        WHERE reporting_year = _year AND country_code = country AND status = 'approved'
            AND approved_at IS NOT NULL AND deleted_at IS NULL
        ORDER BY approved_at DESC, id DESC LIMIT 1 FOR SHARE;
        IF approved_id IS NULL THEN
            RAISE EXCEPTION 'CWIS approval required: approve Equity data for year % and country % first', _year, country;
        END IF;
    END IF;
    INSERT INTO cwis.generation_runs(year, country_code, equity_submission_id, generated_at)
    VALUES (_year, country, approved_id, NOW())
    ON CONFLICT (year) DO UPDATE SET country_code = EXCLUDED.country_code,
        equity_submission_id = EXCLUDED.equity_submission_id, generated_at = EXCLUDED.generated_at;
    -- Existing insert function still creates the catalogue rows and calls the legacy master.
    PERFORM public.insert_data_into_cwis_table(_year);
    FOR item IN SELECT * FROM (VALUES
        ('2', 'EQ-2'), ('3', 'EQ-3'), ('4', 'EQ-4'), ('4a', 'EQ-4a'), ('5', 'EQ-5'),
        ('6_1', 'EQ-6.1'), ('6_1a', 'EQ-6.1a'), ('6_1b', 'EQ-6.1b'), ('6_2', 'EQ-6.2'),
        ('6_3', 'EQ-6.3'), ('6_3a', 'EQ-6.3a'), ('6_3b', 'EQ-6.3b'),
        ('6_4', 'EQ-6.4'), ('6_5', 'EQ-6.5')
    ) AS definitions(suffix, code) LOOP
        IF cwis.indicator_applies_to_country(country, item.code) THEN
            EXECUTE format('SELECT public.%I($1)', 'update_data_into_cwis_table_eq_' || item.suffix || '_newsan') USING _year;
        END IF;
    END LOOP;
END;
$function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_2_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-2'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_3_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-3'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_4_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-4'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_4a_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-4a'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_5_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-5'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_1_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.1'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_1a_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.1a'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_1b_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.1b'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_2_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.2'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_3_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.3'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_3a_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.3a'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_3b_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.3b'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_4_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.4'); END; $function$;


CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_eq_6_5_newsan(_year integer)
 RETURNS void
 LANGUAGE plpgsql
AS $function$ BEGIN PERFORM cwis.update_equity_indicator(_year, 'EQ-6.5'); END; $function$;
