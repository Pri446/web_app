CREATE OR REPLACE FUNCTION cwis.require_approved_equity_submission(
    _submission_id bigint, _country varchar, _indicator_code varchar
) RETURNS bigint LANGUAGE plpgsql STABLE AS $$
DECLARE
    s cwis.equity_submissions%ROWTYPE;
BEGIN
    IF _country IS NULL OR _country NOT IN ('ALL', 'NP', 'BD') THEN
        RAISE EXCEPTION 'Invalid country';
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
    IF NOT EXISTS (
        SELECT 1 FROM cwis.data_source ds
        WHERE ds.indicator_code = _indicator_code AND ds.outcome = 'equity'
          AND (_country = 'ALL' OR EXISTS (
              SELECT 1 FROM cwis.indicator_country m
              WHERE m.data_source_id = ds.id AND m.country_code = _country
          ))
    ) THEN
        RAISE EXCEPTION 'Indicator % is unavailable for %', _indicator_code, _country;
    END IF;
    RETURN s.id;
END;
$$;
