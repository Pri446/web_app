<?php

namespace Database\Seeders\Cwis\Equity;

use DB;
use App\Models\Cwis\DataSource;
use Illuminate\Database\Seeder;

class CwisDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datasources =  array(
            [1, 'equity', 'EQ-1', 'Ratio of LIC access to total population access'], 
            [2, 'safety', 'SF-1a', 'Percentage of population with access to safe, private, individual toilets/latrines'], 
            [3, 'safety', 'SF-1b', 'Percentage of on-site sanitation that have been desludged'], 
            [4, 'safety', 'SF-1c', 'Percentage of collected FS disposed at a treatment plant or at designated disposal site'], 
            [5, 'safety', 'SF-1d', 'FS treatment capacity as a percentage of total FS generated from NSS connections (excluding safely disposed in situ)'], 
            [6, 'safety', 'SF-1e', 'FS treatment capacity as a percentage of total FS collected from NSS connections'], 
            [7, 'safety', 'SF-1f', 'Wastewater treatment capacity as a percentage of total wastewater generated from sewered connections and greywater generated from non-sewered connections'], 
            [8, 'safety', 'SF-1g', 'Effectiveness of FS/WW treatment in meeting prescribed standards for effluent discharge and biosolids disposal'], 
            [9, 'safety', 'SF-2a', 'Percentage LIC population with access to safe individual toilets'], 
            [10, 'safety', 'SF-2b', 'Percentage of LIC, NSS, IHHLs that have been desludged'], 
            [11, 'safety', 'SF-2c', 'Percentage of collected FS (collected from LIC) disposed at treatment plant or designated disposal sites'], 
            [12, 'safety', 'SF-3', 'Percentage of dependent population (those without access to a private toilet/latrine) with access to safe shared facilities (CT/PT)'], 
            [13, 'safety', 'SF-3b', 'Percentage of CTs that adhere to principles of universal design'], 
            [14, 'safety', 'SF-3c', 'Percentage of users of CTs that are women'],
            [15, 'safety', 'SF-3e', 'Average distance from the house to the closest CT (in meters)'], 
            [16, 'safety', 'SF-4a', 'Percentage of PTs where FS and WW generated is safely transported to TP or safely disposed in situ'], 
            [17, 'safety', 'SF-4b', 'Percentage of PTs that adhere to principles of universal design'], 
            [18, 'safety', 'SF-4d', 'Percentage of users of PTs that are women'], 
            [19, 'safety', 'SF-5', 'Percentage of educational institutions where FS/WW generated is safely transported to TP or safely disposed in situ'], 
            [20, 'safety', 'SF-6', 'Percentage of healthcare facilities where FS/WW generated is safely transported to TP or safely disposed in situ'], 
            [21, 'safety', 'SF-7', 'Percentage of desludging services completed mechanically or semi-mechanically (by a gulper)'],
            [22, 'safety', 'SF-9', 'Percentage of tests which are in compliance with water quality standards for fecal coliform']
        );
     
     foreach ($datasources as $datasource) {
    
         $existDataSource =  DB::table('cwis.data_source')
                 ->where('indicator_code', $datasource[2])
                 ->first();
         if(!$existDataSource) {
            DataSource::insert([
             'id' => $datasource[0],
             'outcome' => $datasource[1], 
             'indicator_code' => $datasource[2],
             'label' => $datasource[3], 
         ] );
         }
     }

     $equityDatasources = array(
        ['equity', 'EQ-2', 'Equity of Access to Safely Managed Sanitation'],
        ['equity', 'EQ-3', 'Equity of Subsidies'],
        ['equity', 'EQ-4', 'Gender equity in sanitation workforce'],
        ['equity', 'EQ-4a', 'Gender equity in sanitation leadership'],
        ['equity', 'EQ-5', 'Gender pay gap in the sanitation workforce'],
        ['equity', 'EQ-6.1', 'Training/certification is required to be a sanitation worker'],
        ['equity', 'EQ-6.1a', 'Training covers labor rights and recourse'],
        ['equity', 'EQ-6.1b', 'Training covers occupational safety, health risks, and Standard Operating Procedures (SOP)'],
        ['equity', 'EQ-6.2', 'All sanitation workers have a formal channel for legal recourse'],
        ['equity', 'EQ-6.3', 'Workers have the right to unionize'],
        ['equity', 'EQ-6.3a', 'Operational worker unions exist'],
        ['equity', 'EQ-6.3b', 'Support is offered by the city to run the union'],
        ['equity', 'EQ-6.4', 'All sanitation workers are covered by social security'],
        ['equity', 'EQ-6.5', 'All sanitation workers are covered by health insurance'],
     );

     $equityDatasources[] = ['safety', 'SF-1', 'Access to safely managed sanitation'];
     $equityDatasources[] = ['safety', 'SF-0a', 'Number of buildings with safely managed sanitation'];
     $equityDatasources[] = ['safety', 'SF-0b', 'Number of households with safely managed sanitation'];
     $equityDatasources[] = ['safety', 'SF-0c', 'Population with safely managed sanitation'];
     $nextId = ((int) DB::table('cwis.data_source')->max('id')) + 1;

     foreach ($equityDatasources as $datasource) {
        $existDataSource = DB::table('cwis.data_source')
            ->where('indicator_code', $datasource[1])
            ->first();

        if ($existDataSource) {
            DB::table('cwis.data_source')
                ->where('indicator_code', $datasource[1])
                ->update([
                    'outcome' => $datasource[0],
                    'label' => $datasource[2],
                ]);
        } else {
            DataSource::insert([
                'id' => $nextId++,
                'outcome' => $datasource[0],
                'indicator_code' => $datasource[1],
                'label' => $datasource[2],
            ]);
        }
     }

    }
}
