-- Equity calculations. No official-result writes or approval changes are performed here.
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

CREATE OR REPLACE FUNCTION cwis.equity_policy_result(
    _answer boolean, _document_id bigint
) RETURNS text LANGUAGE sql STABLE AS $$
    SELECT CASE
        WHEN _answer IS NULL THEN NULL
        WHEN _answer = false THEN 'No'
        WHEN EXISTS (
            SELECT 1 FROM cwis.equity_documents
            WHERE id = _document_id AND path IS NOT NULL AND path <> ''
        ) THEN 'Yes'
        ELSE NULL
    END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_2(
    _a numeric, _b numeric, _c numeric, _d numeric
) RETURNS numeric LANGUAGE plpgsql IMMUTABLE AS $$
BEGIN
    IF _a IS NULL OR _b IS NULL OR _c IS NULL OR _d IS NULL THEN
        RETURN NULL;
    END IF;
    IF _a < 0 OR _b < 0 OR _c < 0 OR _d < 0
       OR _a > _b OR _a > _c OR _b > _d OR _c > _d THEN
        RAISE EXCEPTION 'Inconsistent EQ-2 population aggregates';
    END IF;
    RETURN (_a / NULLIF(_b, 0)) / NULLIF((_c / NULLIF(_d, 0)), 0);
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_3(
    _submission_id bigint, _country varchar
) RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result numeric;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-3');
    SELECT p.total_subsidies_amount_nss / NULLIF(p.total_subsidies_amount_ss, 0)
    INTO result FROM cwis.equity_subsidies p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_4(
    _submission_id bigint, _country varchar
) RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result numeric;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-4');
    SELECT p.women_employee_count::numeric / NULLIF(p.total_employee_count, 0) * 100
    INTO result FROM cwis.sanitation_personnel_snapshot p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_4a(
    _submission_id bigint, _country varchar
) RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result numeric;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-4a');
    SELECT p.women_leadership_count::numeric / NULLIF(p.total_leadership_count, 0) * 100
    INTO result FROM cwis.sanitation_personnel_snapshot p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_5(
    _submission_id bigint, _country varchar
) RETURNS numeric LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result numeric;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-5');
    SELECT (p.average_salary_of_men_in_sanitation - p.average_salary_of_women_in_sanitation) / NULLIF(p.average_salary_of_men_in_sanitation, 0)
    INTO result FROM cwis.sanitation_personnel_snapshot p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_1(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.1');
    SELECT cwis.equity_policy_result(p.training_certification_required, p.training_requirement_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_1a(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
    parent_result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.1a');
    parent_result := cwis.calculate_eq_6_1(_submission_id, _country);
    IF parent_result = 'No' THEN
        RETURN 'Not applicable';
    ELSIF parent_result IS DISTINCT FROM 'Yes' THEN
        RETURN NULL;
    END IF;
    SELECT cwis.equity_policy_result(p.covers_labor_rights_and_recourse, p.labor_rights_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_1b(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
    parent_result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.1b');
    parent_result := cwis.calculate_eq_6_1(_submission_id, _country);
    IF parent_result = 'No' THEN
        RETURN 'Not applicable';
    ELSIF parent_result IS DISTINCT FROM 'Yes' THEN
        RETURN NULL;
    END IF;
    SELECT cwis.equity_policy_result(p.covers_safety_health_and_sop, p.safety_health_sop_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_2(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.2');
    SELECT cwis.equity_policy_result(p.legal_recourse_available_to_all, p.legal_recourse_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_3(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.3');
    SELECT cwis.equity_policy_result(p.worker_union_exists, p.worker_union_registration_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_3a(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
    parent_result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.3a');
    parent_result := cwis.calculate_eq_6_3(_submission_id, _country);
    IF parent_result = 'No' THEN
        RETURN 'Not applicable';
    ELSIF parent_result IS DISTINCT FROM 'Yes' THEN
        RETURN NULL;
    END IF;
    SELECT cwis.equity_policy_result(p.worker_union_operational, p.worker_union_operation_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_3b(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
    parent_result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.3b');
    parent_result := cwis.calculate_eq_6_3(_submission_id, _country);
    IF parent_result = 'No' THEN
        RETURN 'Not applicable';
    ELSIF parent_result IS DISTINCT FROM 'Yes' THEN
        RETURN NULL;
    END IF;
    SELECT cwis.equity_policy_result(p.city_supports_worker_union, p.worker_union_support_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_4(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.4');
    SELECT cwis.equity_policy_result(p.all_workers_social_security_covered, p.social_security_coverage_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION cwis.calculate_eq_6_5(
    _submission_id bigint, _country varchar
) RETURNS text LANGUAGE plpgsql STABLE AS $$
DECLARE
    source_id bigint;
    result text;
BEGIN
    source_id := cwis.require_approved_equity_submission(_submission_id, _country, 'EQ-6.5');
    SELECT cwis.equity_policy_result(p.all_workers_health_insurance_covered, p.health_insurance_coverage_document_id)
    INTO result FROM cwis.sanitation_worker_policy p
    WHERE p.submission_id = source_id AND p.deleted_at IS NULL;
    RETURN result;
END;
$$;
