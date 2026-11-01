-- Shared country applicability for every registered CWIS outcome.
CREATE OR REPLACE FUNCTION cwis.indicators_for_country(_country varchar)
RETURNS SETOF cwis.data_source LANGUAGE plpgsql STABLE AS $$
BEGIN
    IF _country IS NULL OR _country NOT IN ('ALL', 'NP', 'BD') THEN
        RAISE EXCEPTION 'Invalid country';
    END IF;
    RETURN QUERY
    SELECT ds.* FROM cwis.data_source ds
    WHERE _country = 'ALL' OR EXISTS (
        SELECT 1 FROM cwis.indicator_country m
        WHERE m.data_source_id = ds.id AND m.country_code = _country
    )
    ORDER BY ds.id;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.indicator_applies_to_country(
    _country varchar, _indicator_code varchar
) RETURNS boolean LANGUAGE sql STABLE AS $$
    SELECT EXISTS (
        SELECT 1 FROM cwis.indicators_for_country(_country) ds
        WHERE ds.indicator_code = _indicator_code
    );
$$;

CREATE OR REPLACE FUNCTION cwis.require_indicator_country(
    _country varchar, _indicator_code varchar
) RETURNS varchar LANGUAGE plpgsql STABLE AS $$
BEGIN
    IF NOT cwis.indicator_applies_to_country(_country, _indicator_code) THEN
        RAISE EXCEPTION 'Indicator % is unavailable for %', _indicator_code, _country;
    END IF;
    RETURN _indicator_code;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.require_approved_equity_submission(
    _submission_id bigint, _country varchar, _indicator_code varchar
) RETURNS bigint LANGUAGE plpgsql STABLE AS $$
DECLARE
    s cwis.equity_submissions%ROWTYPE;
BEGIN
    PERFORM cwis.require_indicator_country(_country, _indicator_code);
    IF NOT EXISTS (
        SELECT 1 FROM cwis.data_source
        WHERE indicator_code = _indicator_code AND outcome = 'equity'
    ) THEN
        RAISE EXCEPTION 'Indicator % is not an Equity indicator', _indicator_code;
    END IF;
    SELECT * INTO s FROM cwis.equity_submissions
    WHERE id = _submission_id AND deleted_at IS NULL;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Equity submission not found';
    END IF;
    IF s.status IS DISTINCT FROM 'approved' OR s.approved_at IS NULL THEN
        RAISE EXCEPTION 'Official calculation requires an approved submission';
    END IF;
    IF s.country_code IS DISTINCT FROM _country THEN
        RAISE EXCEPTION 'Submission country does not match requested country';
    END IF;
    RETURN s.id;
END;
$$;
