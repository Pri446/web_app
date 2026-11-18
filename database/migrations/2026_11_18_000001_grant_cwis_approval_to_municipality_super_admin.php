<?php

use Database\Seeders\CwisApprovalPermissionSeeder;
use Illuminate\Database\Migrations\Migration;

class GrantCwisApprovalToMunicipalitySuperAdmin extends Migration
{
    public function up()
    {
        (new CwisApprovalPermissionSeeder())->run();
    }

    public function down()
    {
        // Preserve grants that may have existed before this additive repair.
    }
}
