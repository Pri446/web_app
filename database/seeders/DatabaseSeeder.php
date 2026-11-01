<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PermissionsSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(UsersTableSeeder::class);

        // $this->call(Fsm\EmployeeInfoSeeder::class);


        // LookUp Table Seeders
        // Building Info
        $this->call(BuildingInfo\FunctionalUseSeeder::class);
        $this->call(BuildingInfo\UseCategorySeeder::class);
        $this->call(BuildingInfo\StructureTypeSeeder::class);
        $this->call(BuildingInfo\SanitationSystemTypeSeeder::class);
        $this->call(BuildingInfo\WaterSourceSeeder::class);

        // FSM
        $this->call(Fsm\ContainmentTypesSeeder::class);
        $this->call(Fsm\KeyPerformanceIndicatorSeeder::class);
        $this->call(Fsm\TreatmentPlantEfficiencySeeder::class);

        // Payment Modules
        $this->call(SwmInfoSeeder::class);
        $this->call(TaxPaymentInfoSeeder::class);
        $this->call(WaterSupplyInfoSeeder::class);

        // WMS Layers
        $this->call(WmsLinkSeeder::class);

        // CWIS
        $this->call(CwisSettingsSeeder::class);
        $this->call(\Database\Seeders\Cwis\Equity\CwisDataSourceSeeder::class);

        // Multilingual
        $this->call(LanguageSeeder::class);
    }
}
