<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCwisCountrySettingAndSubmissionContext extends Migration
{
    public function up()
    {
        Schema::table('cwis.equity_submissions', function (Blueprint $table) {
            $table->string('country_code', 3)->nullable();
            $table->index(['country_code', 'reporting_year'], 'idx_equity_country_year');
        });
        DB::statement("ALTER TABLE cwis.equity_submissions ADD CONSTRAINT chk_equity_country CHECK (country_code IN ('ALL', 'NP', 'BD'))");

        DB::statement('LOCK TABLE public.site_settings IN SHARE ROW EXCLUSIVE MODE');
        $exists = DB::table('public.site_settings')->where('category', 'cwis_setting')
            ->where('name', 'active_country')->exists();
        if (!$exists) {
            DB::table('public.site_settings')->insert([
                'id' => ((int) DB::table('public.site_settings')->max('id')) + 1,
                'name' => 'active_country', 'category' => 'cwis_setting', 'value' => 'ALL',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::statement('ALTER TABLE cwis.equity_submissions DROP CONSTRAINT chk_equity_country');
        Schema::table('cwis.equity_submissions', function (Blueprint $table) {
            $table->dropIndex('idx_equity_country_year');
            $table->dropColumn('country_code');
        });
    }
}
