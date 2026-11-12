<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use DB;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;

class BuildingsRoadExport implements FromView, WithTitle, WithEvents
{
    private $codes;
    
    /**
     * BuildingsExport constructor.
     *
     * @param $codes
     */
    public function __construct($codes)
    {
        $this->codes = $codes;
    }

     /**
     * Generates the view for exporting.
     *
     * @return View
     */
    public function view(): View
    {
      
         if($this->codes > 0){
            $roadCodesStr = $this->codes;
        } else {
            $roadCodesStr = 0;
        }
    $roadCodes = explode (",", $roadCodesStr); 
    $roadCodes = array_map(function($value) { return "'" . $value . "'"; }, $roadCodes);
    
    $building_query = "SELECT bin, ST_AsText(geom) AS geom"
                        . " FROM building_info.buildings"
                        . " WHERE road_code IN (" . implode(',', $roadCodes) . ")";
    $results = DB::select($building_query);
    $rows = array();
    $total = 0;
    $total_sewer_network = 0;
    $total_drain_network = 0;
    $total_septic_tank = 0;
    $total_pit_holding_tank = 0;
    $total_onsite_treatment = 0;
    $total_composting_toilet = 0;
    $total_water_body = 0;
    $total_open_ground = 0;
    $total_community_toilet = 0;
    $total_open_defacation = 0;
    if(count($results) > 0) {
        // Constructing SQL query to fetch building information based on road codes
         $buildingQuery = "SELECT st.type AS structype, COUNT(*)::integer AS count,
                       COUNT(b.bin) filter (where b.sanitation_system_id = '1')::integer  AS sewer_network,
            COUNT(b.bin) filter (where b.sanitation_system_id = '2')::integer  AS drain_network,
            COUNT(b.bin) filter (where b.sanitation_system_id = '3')::integer AS septic_tank,
            COUNT(b.bin) filter (where b.sanitation_system_id = '4')::integer AS pit_holding_tank,
            COUNT(b.bin) filter (where b.sanitation_system_id = '5')::integer AS onsite_treatment,
            COUNT(b.bin) filter (where b.sanitation_system_id = '6')::integer AS composting_toilet,
            COUNT(b.bin) filter (where b.sanitation_system_id = '7')::integer AS water_body,
            COUNT(b.bin) filter (where b.sanitation_system_id = '8')::integer AS open_ground,
            COUNT(b.bin) filter (where b.sanitation_system_id = '9')::integer AS community_toilet,
            COUNT(b.bin) filter (where b.sanitation_system_id = '10')::integer AS open_defacation
						
                    FROM building_info.buildings b 
                    LEFT JOIN building_info.structure_types st ON b.structure_type_id = st.id"
                . " WHERE b.road_code IN (" . implode(',', $roadCodes) . ")"
                . "  AND b.deleted_at is null"
                . " GROUP BY b.structure_type_id, st.id ORDER BY st.id ASC";
        
            $buildingResults = DB::select($buildingQuery);
           
            foreach($buildingResults as $building) {
                $total += $building->count;
                $total_sewer_network += $building->sewer_network;
                $total_drain_network += $building->drain_network;
                $total_septic_tank += $building->septic_tank;
                $total_pit_holding_tank += $building->pit_holding_tank;
                $total_onsite_treatment += $building->onsite_treatment;
                $total_composting_toilet += $building->composting_toilet;
                $total_water_body += $building->water_body;
                $total_open_ground += $building->open_ground;
                $total_community_toilet += $building->community_toilet;
                $total_open_defacation += $building->open_defacation;
                $rows[] = array(
                    'Structure Type' => $building->structype,
                    'Buildings' => $building->count,
                    'Sewer Network' => $building->sewer_network,
                    'Drain Network' => $building->drain_network,
                    'Septic Tank' => $building->septic_tank,
                    'Pit / Holding Tank' => $building->pit_holding_tank,
                    'Onsite Treatment' => $building->onsite_treatment,
                    'Composting Toilet' => $building->composting_toilet,
                    'Water Body' => $building->water_body,
                    'Open Ground' => $building->open_ground,
                    'Community Toilet' => $building->community_toilet,
                    'Open Defecation' => $building->open_defacation,
                    );
                }
        }
                $rows[] = array(
                    'Structure Type' => 'Total',
                    'Buildings' => $total,
                    'Sewer Network' => $total_sewer_network,
                    'Drain Network' => $total_drain_network,
                    'Septic Tank' => $total_septic_tank,
                    'Pit / Holding Tank' => $total_pit_holding_tank,
                    'Onsite Treatment' => $total_onsite_treatment,
                    'Composting Toilet' => $total_composting_toilet,
                    'Water Body' => $total_water_body,
                    'Open Ground' => $total_open_ground,
                    'Community Toilet' => $total_community_toilet,
                    'Open Defecation' => $total_open_defacation,
                    );

        return view('exports.buildings', compact('rows'));
    }

    /**
     * Registers events for the export.
     *
     * @return array
     */
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
        return 'Buildings Sheet';
    }
}