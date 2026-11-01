-- Revised SF-1 family. Public signatures remain compatible with the legacy master.
CREATE OR REPLACE FUNCTION cwis.safety_setting_numeric(_name text, _minimum numeric, _maximum numeric)
RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE
    setting numeric;
BEGIN
    SELECT value::numeric INTO setting FROM public.site_settings
    WHERE category = 'cwis_setting' AND name = _name AND deleted_at IS NULL
    ORDER BY id DESC LIMIT 1;
    IF setting IS NULL OR setting::text IN ('NaN','Infinity','-Infinity')
        OR setting < _minimum OR setting > _maximum THEN
        RAISE EXCEPTION 'Missing or invalid CWIS setting: %', _name;
    END IF;
    RETURN setting;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.safety_percentage(_a numeric, _b numeric)
RETURNS numeric LANGUAGE sql IMMUTABLE AS $$
    SELECT CASE WHEN _a >= 0 AND _b > 0 THEN round(100 * _a / _b, 3) END;
$$;

-- One row per building: containment joins must not multiply household/population totals.
CREATE OR REPLACE FUNCTION cwis.safety_buildings(_year integer)
RETURNS TABLE(bin varchar, households numeric, private_households numeric,
    population numeric, private_population numeric, safe boolean,
    system_id integer, containment_type_id integer)
LANGUAGE sql STABLE AS $$
    WITH classified AS (
        SELECT h.bin,
            bool_and(COALESCE(h.safely_managed_sanitation_system = 'yes', false)) AS safe,
            count(DISTINCT h.containment_type_id) AS type_count,
            min(h.containment_type_id) AS type_id
        FROM public.execute_select_build_sanisys_nd_criterias() h
        GROUP BY h.bin
    )
    SELECT b.bin, b.household_served::numeric, b.household_with_private_toilet::numeric,
        b.population_served::numeric, b.population_with_private_toilet::numeric,
        COALESCE(c.safe, false), b.sanitation_system_id,
        CASE WHEN c.type_count = 1 THEN c.type_id END
    FROM building_info.buildings b
    LEFT JOIN classified c ON c.bin = b.bin
    WHERE b.deleted_at IS NULL AND b.construction_year < make_date(_year + 1, 1, 1);
$$;

CREATE OR REPLACE FUNCTION cwis.safety_eligible_containments(_year integer)
RETURNS TABLE(id varchar, emptied_status boolean, last_emptied_date date)
LANGUAGE sql STABLE AS $$
    SELECT DISTINCT c.id, c.emptied_status, c.last_emptied_date
    FROM fsm.containments c
    JOIN public.execute_select_build_sanisys_nd_criterias() h ON h.containment_id = c.id
    WHERE c.deleted_at IS NULL AND c.construction_date < make_date(_year, 1, 1)
        AND h.construction_year < make_date(_year, 1, 1)
        AND h.household_with_private_toilet > 0 AND h.sanitation_system_id IN (3,4,11);
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_safety_revised(_year integer, _code varchar,
    _water numeric DEFAULT 100, _waste numeric DEFAULT 80,
    _sewer_supernatant numeric DEFAULT 80, _grey numeric DEFAULT 60)
RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE
    a numeric;
    b numeric;
    p1 numeric;
    p2 numeric;
    annual_septic_rate numeric;
    annual_pit_rate numeric;
    bod_limit numeric;
    previous_start date := make_date(_year - 1, 1, 1);
    period_start date := make_date(_year, 1, 1);
    period_end date := make_date(_year + 1, 1, 1);
BEGIN
    IF _year IS NULL OR _year NOT BETWEEN 2 AND 9998 THEN
        RAISE EXCEPTION 'Safety reporting year must be between 2 and 9998';
    END IF;
    CASE _code
    WHEN 'SF-1' THEN
        SELECT COALESCE(sum(private_households) FILTER (WHERE safe), 0), sum(households)
        INTO a, b FROM cwis.safety_buildings(_year);
    WHEN 'SF-1a' THEN
        SELECT COALESCE(sum(private_population) FILTER (WHERE safe), 0), sum(population)
        INTO a, b FROM cwis.safety_buildings(_year);
    WHEN 'SF-1b' THEN
        WITH eligible AS (SELECT * FROM cwis.safety_eligible_containments(_year))
        SELECT count(*) FILTER (WHERE EXISTS (
            SELECT 1 FROM fsm.applications app
            JOIN fsm.emptyings e ON e.application_id = app.id
            WHERE app.containment_id = eligible.id AND app.deleted_at IS NULL
                AND app.emptying_status IN (1, 2) AND e.deleted_at IS NULL
                AND e.emptied_date >= previous_start AND e.emptied_date < period_start
        ) OR (emptied_status = true AND last_emptied_date >= previous_start
            AND last_emptied_date < period_start)), count(*)
        INTO a, b FROM eligible;
    WHEN 'SF-1c' THEN
        SELECT sum(volume_of_sludge) INTO a FROM fsm.sludge_collections
        WHERE deleted_at IS NULL AND date >= period_start AND date < period_end;
        SELECT sum(e.volume_of_sludge) INTO b FROM fsm.emptyings e
        JOIN fsm.applications app ON app.id = e.application_id
        WHERE e.deleted_at IS NULL AND app.deleted_at IS NULL AND app.emptying_status IN (1, 2)
            AND e.emptied_date >= period_start AND e.emptied_date < period_end;
    WHEN 'SF-1d' THEN
        annual_septic_rate := cwis.safety_setting_numeric('fs_generation_septic_m3_per_person_year', 0, 1000000);
        annual_pit_rate := cwis.safety_setting_numeric('fs_generation_unlined_pit_m3_per_person_year', 0, 1000000);
        IF EXISTS (SELECT 1 FROM cwis.safety_buildings(_year)
            WHERE population > 0 AND system_id IN (3,4,11) AND containment_type_id IS NULL) THEN
            RETURN NULL;
        END IF;
        SELECT 365 * sum(capacity_per_day) INTO a FROM fsm.treatment_plants
        WHERE deleted_at IS NULL AND status = true AND type IN (3, 4);
        SELECT COALESCE(sum(population) FILTER (WHERE system_id IN (3, 4, 11)
            AND containment_type_id IN (2,3,4,5,6,7,10,11,12,14,15,16,17)), 0),
            COALESCE(sum(population) FILTER (WHERE system_id = 4 AND containment_type_id = 9), 0)
        INTO p1, p2 FROM cwis.safety_buildings(_year);
        b := annual_septic_rate * p1 + annual_pit_rate * p2;
    WHEN 'SF-1e' THEN
        SELECT 365 * sum(capacity_per_day) INTO a FROM fsm.treatment_plants
        WHERE deleted_at IS NULL AND status = true AND type IN (3, 4);
        -- Apply the approved indicator document's daily-volume annualization assumption.
        SELECT 365 * sum(volume_of_sludge) INTO b FROM fsm.sludge_collections
        WHERE deleted_at IS NULL AND date >= previous_start AND date < period_start;
    WHEN 'SF-1f' THEN
        -- Saved CWIS values are authoritative, including calls through legacy signatures.
        _water := cwis.safety_setting_numeric('average_water_consumption_lpcd', 0.000001, 1000000);
        _waste := cwis.safety_setting_numeric('waste_water_conversion_factor', 0, 100);
        _sewer_supernatant := _waste;
        _grey := cwis.safety_setting_numeric('greywater_conversion_factor_not_connected_to_sewer', 0, 100);
        IF EXISTS (SELECT 1 FROM cwis.safety_buildings(_year)
            WHERE population > 0 AND system_id IN (3,4,11) AND containment_type_id IS NULL) THEN
            RETURN NULL;
        END IF;
        IF _water IS NULL OR _water <= 0 OR _waste IS NULL OR _waste NOT BETWEEN 0 AND 100
            OR _sewer_supernatant IS NULL OR _sewer_supernatant NOT BETWEEN 0 AND 100
            OR _grey IS NULL OR _grey NOT BETWEEN 0 AND 100 THEN
            RAISE EXCEPTION 'Invalid Safety water-consumption or conversion parameters';
        END IF;
        SELECT sum(capacity_per_day) / 1000 INTO a FROM fsm.treatment_plants
        WHERE deleted_at IS NULL AND status = true AND type IN (1, 2);
        SELECT sum(population * _water / 1000000 * CASE
            WHEN system_id = 1 THEN _waste / 100
            WHEN system_id IN (3,4,11) AND containment_type_id IN (1,13) THEN _sewer_supernatant / 100
            WHEN system_id IN (3,4,11) AND containment_type_id IN (2,3,4,5,6,7,10,11,12,14,15,16,17) THEN _grey / 100
            WHEN system_id = 4 AND containment_type_id IN (8,9) THEN _grey / 100
            WHEN system_id IN (5,6,7,8) OR system_id IS NULL THEN _grey / 100
            ELSE NULL END)
        INTO b FROM cwis.safety_buildings(_year);
    WHEN 'SF-1g' THEN
        bod_limit := cwis.safety_setting_numeric('bod_compliance_threshold_mg_l', 0.000001, 1000000);
        SELECT count(*) FILTER (WHERE bod < bod_limit), count(*) INTO a, b
        FROM fsm.treatmentplant_tests
        WHERE deleted_at IS NULL AND bod IS NOT NULL
            AND date >= period_start AND date < period_end;
    ELSE
        RAISE EXCEPTION 'Unsupported revised Safety indicator: %', _code;
    END CASE;
    RETURN cwis.safety_percentage(a, b);
END;
$$;

CREATE OR REPLACE FUNCTION cwis.write_safety_revised(_year integer, _code varchar,
    _water numeric DEFAULT 100, _waste numeric DEFAULT 80,
    _sewer_supernatant numeric DEFAULT 80, _grey numeric DEFAULT 60)
RETURNS void LANGUAGE plpgsql AS $$
DECLARE
    result numeric;
    country varchar;
BEGIN
    SELECT COALESCE((SELECT value FROM public.site_settings
        WHERE category = 'cwis_setting' AND name = 'active_country' AND deleted_at IS NULL
        ORDER BY id DESC LIMIT 1), 'ALL') INTO country;
    -- The generation run freezes the country even if site settings change later.
    IF to_regclass('cwis.generation_runs') IS NOT NULL THEN
        EXECUTE 'SELECT COALESCE((SELECT country_code FROM cwis.generation_runs WHERE year = $1), $2)'
            INTO country USING _year, country;
    END IF;
    IF NOT cwis.indicator_applies_to_country(country, _code) THEN RETURN; END IF;
    result := cwis.calculate_safety_revised(_year, _code, _water, _waste, _sewer_supernatant, _grey);
    UPDATE cwis.data_cwis SET data_value = COALESCE(result::text, 'NaN'), updated_at = now()
    WHERE year = _year AND indicator_code = _code AND deleted_at IS NULL;
END;
$$;

CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_1_newsan(_year integer)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_revised(_year, 'SF-1'); $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_1a_newsan(_year integer)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_revised(_year, 'SF-1a'); $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_1b_newsan(_year integer)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_revised(_year, 'SF-1b'); $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_1c_newsan(_year integer)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_revised(_year, 'SF-1c'); $$;
-- Legacy daily-rate arguments are retained for compatibility, not reinterpreted as annual rates.
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_1d_newsan(_year integer,
    _fs_generation_from_containment_not_connected_to_sewer_lpcd numeric,
    _fs_generation_from_permeable_or_unlined_pit_lpcd numeric)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_revised(_year, 'SF-1d'); $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_1e_newsan(_year integer)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_revised(_year, 'SF-1e'); $$;
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_1f_newsan(_year integer,
    _average_water_consumption_lpcd numeric, _waste_water_conversion_factor numeric,
    _greywater_conversion_factor_connected_to_sewer numeric,
    _greywater_conversion_factor_not_connected_to_sewer numeric)
RETURNS void LANGUAGE sql AS $$
    SELECT cwis.write_safety_revised(_year, 'SF-1f', _average_water_consumption_lpcd,
        _waste_water_conversion_factor, _greywater_conversion_factor_connected_to_sewer,
        _greywater_conversion_factor_not_connected_to_sewer);
$$;
-- BOD is strictly below the saved CWIS threshold; TSS/E. coli and biosolids are excluded.
CREATE OR REPLACE FUNCTION public.update_data_into_cwis_table_sf_1g_newsan(_year integer,
    _bod_standard numeric, _tss_standard numeric, _ecoli_standard numeric)
RETURNS void LANGUAGE sql AS $$ SELECT cwis.write_safety_revised(_year, 'SF-1g'); $$;
