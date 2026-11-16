<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use DB;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Auth;
class BuildingsEmptiedListExport implements FromView, WithTitle, WithEvents
{
    private $bufferPolygonGeom;
    private $year;
    
    public function __construct($bufferPolygonGeom, $year)
    {
        $this->geom = $bufferPolygonGeom;
        $this->year = $year;
    }
    public function view(): View
    {
       
         if($this->year == 'All'){
                $yearOn = "";
            } else {
                $yearOn = "AND extract(year from e.created_at) = '$this->year'";
            }
            
        if (Auth::user()->hasRole('Service Provider - Admin')){
            $whereUser = " AND a.service_provider_id =" . Auth::user()->service_provider_id;
        }else{
            $whereUser = " AND a.user_id = " . Auth::id();
        }
            



        /**No of containment emptied**/
        
        $buildingQuery = "SELECT b.*, st.type AS structure_type , fu.name AS functionaluse, ws.source AS water_source, ss.sanitation_system AS sanitation_system ,
        bo.owner_name,
        bo.owner_gender,
        bo.nid,
        bo.owner_contact,
        t.name as toilet_name, lic.community_name as community_name
        FROM (select m as month_val from GENERATE_SERIES(1,12) m) AS months  LEFT JOIN  fsm.emptyings e 
        ON months.month_val = extract(month from e.created_at)
        $yearOn
        LEFT JOIN fsm.applications a ON e.application_id = a.id
        LEFT JOIN fsm.containments c ON c.id = a.containment_id
        AND (ST_Intersects(c.geom, ST_GeomFromText('" . $this->geom . "', 4326)))
        LEFT JOIN building_info.build_contains bc ON bc.containment_id = c.id
        LEFT JOIN building_info.buildings b ON bc.bin = b.bin
        LEFT JOIN building_info.owners bo ON b.bin = bo.bin
        LEFT JOIN building_info.structure_types st ON b.structure_type_id = st.id
        LEFT JOIN building_info.functional_uses fu ON b.functional_use_id = fu.id
        LEFT JOIN layer_info.low_income_communities lic ON lic.id = b.lic_id
        LEFT JOIN building_info.sanitation_systems ss ON b.sanitation_system_id = ss.id
        LEFT JOIN building_info.water_sources ws ON b.water_source_id = ws.id
        LEFT JOIN fsm.build_toilets bt ON bt.bin = b.bin  AND bt.deleted_at IS NULL  
        LEFT JOIN fsm.toilets t ON bt.toilet_id = t.id
        AND e.deleted_at is null
        AND b.deleted_at is null
        ORDER BY c.id ASC";
        $buildingResults = DB::select($buildingQuery);
         
      
        return view('exports.buildings-list', compact('buildingResults'));
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:B1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ]
                ]);
            }
        ];
    }
     /**
     * @return string
     */
    public function title(): string
    {
        return 'Buildings List';
    }
}