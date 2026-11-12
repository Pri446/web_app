-- SF-0 counts use the same one-row-per-building classification as revised Safety.
CREATE OR REPLACE FUNCTION cwis.calculate_safety_count(_year integer, _code varchar)
RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE
    result numeric;
BEGIN
    IF _year IS NULL OR _year NOT BETWEEN 2 AND 9998 THEN
        RAISE EXCEPTION 'Safety reporting year must be between 2 and 9998';
    END IF;
    CASE _code
    WHEN 'SF-0a' THEN
        SELECT count(DISTINCT bin) INTO result FROM cwis.safety_buildings(_year) WHERE safe;
    WHEN 'SF-0b' THEN
        SELECT CASE WHEN count(*) FILTER (WHERE private_households IS NULL OR private_households < 0) > 0
            THEN NULL ELSE COALESCE(sum(private_households), 0) END
        INTO result FROM cwis.safety_buildings(_year) WHERE safe;
    WHEN 'SF-0c' THEN
        SELECT CASE WHEN count(*) FILTER (WHERE private_population IS NULL OR private_population < 0) > 0
            THEN NULL ELSE COALESCE(sum(private_population), 0) END
        INTO result FROM cwis.safety_buildings(_year) WHERE safe;
    ELSE
        RAISE EXCEPTION 'Unsupported Safety count indicator: %', _code;
    END CASE;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_sf_0a(_year integer)
RETURNS numeric LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_safety_count(_year, 'SF-0a'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_sf_0b(_year integer)
RETURNS numeric LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_safety_count(_year, 'SF-0b'); $$;
CREATE OR REPLACE FUNCTION cwis.calculate_sf_0c(_year integer)
RETURNS numeric LANGUAGE sql STABLE AS $$ SELECT cwis.calculate_safety_count(_year, 'SF-0c'); $$;

CREATE OR REPLACE FUNCTION cwis.write_safety_count(_year integer, _code varchar)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE
    country varchar;
    result numeric;
BEGIN
    SELECT COALESCE(
        (SELECT country_code FROM cwis.generation_runs WHERE year = _year),
        (SELECT value FROM public.site_settings WHERE category = 'cwis_setting'
            AND name = 'active_country' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1),
        'ALL') INTO country;
    IF NOT cwis.indicator_applies_to_country(country, _code) THEN RETURN; END IF;
    CASE _code
    WHEN 'SF-0a' THEN result := cwis.calculate_sf_0a(_year);
    WHEN 'SF-0b' THEN result := cwis.calculate_sf_0b(_year);
    WHEN 'SF-0c' THEN result := cwis.calculate_sf_0c(_year);
    ELSE RAISE EXCEPTION 'Unsupported Safety count indicator: %', _code;
    END CASE;
    UPDATE cwis.data_cwis SET data_value = COALESCE(result::text, 'NaN'), updated_at = now()
    WHERE year = _year AND indicator_code = _code AND deleted_at IS NULL;
END;
$$;

CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_0a_newsan(_year integer)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_count(_year, 'SF-0a'); $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_0b_newsan(_year integer)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_count(_year, 'SF-0b'); $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_0c_newsan(_year integer)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_count(_year, 'SF-0c'); $$;

CREATE OR REPLACE FUNCTION cwis.dispatch_safety_counts(_year integer)
RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    PERFORM public.update_data_into_cwis_table_sf_0a_newsan(_year);
    PERFORM public.update_data_into_cwis_table_sf_0b_newsan(_year);
    PERFORM public.update_data_into_cwis_table_sf_0c_newsan(_year);
END;
$$;
