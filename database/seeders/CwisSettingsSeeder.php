<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Fsm\CwisSetting;
use DB;

class CwisSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types =  array(
            [ 1 , "average_water_consumption_lpcd" , 150 , "cwis_setting" ],
            [ 2 , "waste_water_conversion_factor" , 80 , "cwis_setting" ],
            [ 3 , "greywater_conversion_factor_connected_to_sewer" , 80 , "cwis_setting" ],
            [ 4 , "greywater_conversion_factor_not_connected_to_sewer" , 80 , "cwis_setting" ],
            [ 5 , "fs_generation_from_containment_not_connected_to_sewer_lpcd" , 270 , "cwis_setting" ],
            [ 6 , "fs_generation_from_permeable_or_unlined_pit_lpcd" , 280 , "cwis_setting" ],
            [ 7 , "active_country" , "ALL" , "cwis_setting" ]
        );
        
     foreach ($types as $type) {
    
         $existCwisSettings =  DB::table('public.site_settings')
                 ->where('name', $type[1])
                 ->first();
         if(!$existCwisSettings) {
            $id = DB::table('public.site_settings')->where('id', $type[0])->exists()
                ? ((int) DB::table('public.site_settings')->max('id')) + 1
                : $type[0];
            CwisSetting::insert([
             'id' => $id,
             'name' => $type[1],
             'value' => $type[2],
             'category' => $type[3]
         ]);
         }
     }

     foreach (config('cwis_safety_settings') as $name => $parameter) {
         if (!DB::table('public.site_settings')->where('category', 'cwis_setting')
             ->where('name', $name)->whereNull('deleted_at')->exists()) {
             DB::table('public.site_settings')->insert([
                 'id' => ((int) DB::table('public.site_settings')->max('id')) + 1,
                 'name' => $name, 'value' => $parameter['default'], 'category' => 'cwis_setting',
                 'created_at' => now(), 'updated_at' => now(),
             ]);
         }
     }

    }
}
