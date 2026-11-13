CREATE OR REPLACE FUNCTION building_info.generate_missing_public_ids()
RETURNS TABLE (
    buildings_updated BIGINT,
    containments_updated BIGINT
)
LANGUAGE plpgsql
AS $function$
DECLARE
    building_count BIGINT := 0;
    containment_count BIGINT := 0;
    building_public_id_type REGTYPE;
    containment_public_id_type REGTYPE;
    builtup_trigger_state "char";
    landuse_trigger_state "char";
BEGIN
    -- Prevent two administrators from running the backfill simultaneously.
    PERFORM pg_advisory_xact_lock(hashtext('building_info.generate_missing_public_ids'));

    SELECT attribute.atttypid::regtype
    INTO building_public_id_type
    FROM pg_attribute AS attribute
    WHERE attribute.attrelid = 'building_info.buildings'::regclass
      AND attribute.attname = 'public_id'
      AND NOT attribute.attisdropped;

    SELECT attribute.atttypid::regtype
    INTO containment_public_id_type
    FROM pg_attribute AS attribute
    WHERE attribute.attrelid = 'fsm.containments'::regclass
      AND attribute.attname = 'public_id'
      AND NOT attribute.attisdropped;

    IF building_public_id_type IS NULL OR containment_public_id_type IS NULL THEN
        RAISE EXCEPTION 'The public_id column must exist on both buildings and containments.';
    END IF;

    -- Support installations where public_id is either UUID or text.
    IF building_public_id_type = 'uuid'::regtype THEN
        UPDATE building_info.buildings
        SET public_id = gen_random_uuid()
        WHERE public_id IS NULL OR btrim(public_id::text) = '';
    ELSE
        UPDATE building_info.buildings
        SET public_id = gen_random_uuid()::text
        WHERE public_id IS NULL OR btrim(public_id::text) = '';
    END IF;

    GET DIAGNOSTICS building_count = ROW_COUNT;

    -- public_id is not used by these summaries. Preserve their current state,
    -- disable enabled triggers during the bulk update, and restore them after it.
    SELECT trigger.tgenabled
    INTO builtup_trigger_state
    FROM pg_trigger AS trigger
    WHERE trigger.tgrelid = 'fsm.containments'::regclass
      AND trigger.tgname = 'tgr_set_builtupperwardsummary'
      AND NOT trigger.tgisinternal;

    SELECT trigger.tgenabled
    INTO landuse_trigger_state
    FROM pg_trigger AS trigger
    WHERE trigger.tgrelid = 'fsm.containments'::regclass
      AND trigger.tgname = 'tgr_set_landusesummary'
      AND NOT trigger.tgisinternal;

    IF builtup_trigger_state IS NOT NULL AND builtup_trigger_state <> 'D' THEN
        ALTER TABLE fsm.containments DISABLE TRIGGER tgr_set_builtupperwardsummary;
    END IF;

    IF landuse_trigger_state IS NOT NULL AND landuse_trigger_state <> 'D' THEN
        ALTER TABLE fsm.containments DISABLE TRIGGER tgr_set_landusesummary;
    END IF;

    IF containment_public_id_type = 'uuid'::regtype THEN
        UPDATE fsm.containments
        SET public_id = gen_random_uuid()
        WHERE public_id IS NULL OR btrim(public_id::text) = '';
    ELSE
        UPDATE fsm.containments
        SET public_id = gen_random_uuid()::text
        WHERE public_id IS NULL OR btrim(public_id::text) = '';
    END IF;

    GET DIAGNOSTICS containment_count = ROW_COUNT;

    IF builtup_trigger_state = 'O' THEN
        ALTER TABLE fsm.containments ENABLE TRIGGER tgr_set_builtupperwardsummary;
    ELSIF builtup_trigger_state = 'A' THEN
        ALTER TABLE fsm.containments ENABLE ALWAYS TRIGGER tgr_set_builtupperwardsummary;
    ELSIF builtup_trigger_state = 'R' THEN
        ALTER TABLE fsm.containments ENABLE REPLICA TRIGGER tgr_set_builtupperwardsummary;
    END IF;

    IF landuse_trigger_state = 'O' THEN
        ALTER TABLE fsm.containments ENABLE TRIGGER tgr_set_landusesummary;
    ELSIF landuse_trigger_state = 'A' THEN
        ALTER TABLE fsm.containments ENABLE ALWAYS TRIGGER tgr_set_landusesummary;
    ELSIF landuse_trigger_state = 'R' THEN
        ALTER TABLE fsm.containments ENABLE REPLICA TRIGGER tgr_set_landusesummary;
    END IF;

    RETURN QUERY SELECT building_count, containment_count;
END;
$function$;
