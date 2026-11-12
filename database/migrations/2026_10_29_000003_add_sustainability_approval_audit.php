<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSustainabilityApprovalAudit extends Migration
{
    public function up()
    {
        Schema::table('cwis.sustainability_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('cwis.sustainability_submissions', function (Blueprint $table) {
            $table->dropColumn(['submitted_by', 'submitted_at', 'approved_by', 'approved_at']);
        });
    }
}
