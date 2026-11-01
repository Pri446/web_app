<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LimitCwisResultDecimalPlaces extends Migration
{
    public function up()
    {
        DB::unprepared(<<<'SQL'
CREATE FUNCTION cwis.round_saved_indicator_value()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF NEW.indicator_code ~ '^(EQ|SF)-'
        AND btrim(NEW.data_value) ~ '^[+-]?([0-9]+([.][0-9]*)?|[.][0-9]+)([eE][+-]?[0-9]+)?$' THEN
        NEW.data_value := round(btrim(NEW.data_value)::numeric, 3)::text;
    END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER round_cwis_saved_results
BEFORE INSERT OR UPDATE OF data_value ON cwis.data_cwis
FOR EACH ROW EXECUTE FUNCTION cwis.round_saved_indicator_value();
SQL
        );
    }

    public function down()
    {
        DB::statement('DROP TRIGGER IF EXISTS round_cwis_saved_results ON cwis.data_cwis');
        DB::statement('DROP FUNCTION IF EXISTS cwis.round_saved_indicator_value()');
    }
}
