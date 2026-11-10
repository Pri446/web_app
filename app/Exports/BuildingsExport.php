<?php
// Last Modified Date: 12-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use DB;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;

class BuildingsExport implements FromView, WithTitle, WithEvents
{
   
    private $bufferPolygonGeom;
    private $bufferPolygonDistance;

    /**
     * BuildingsExport constructor.
     *
     * @param $bufferPolygonGeom
     * @param $bufferPolygonDistance
     */
    public function __construct($bufferPolygonGeom, $bufferPolygonDistance)
    {
        $this->geom = $bufferPolygonGeom;
        $this->distance = $bufferPolygonDistance;
    }

     /**
     * Generates the view for exporting.
     *
     * @return View
     */
    public function view(): View
    {
        if($this->distance > 0){
                $bufferDisancePolygon = $this->distance;
        } else {
                $bufferDisancePolygon = 0;
        }

        // Construct SQL query to retrieve building data within the buffered polygon
        
        $allBuildingResults = []; // Array to store results

        if (is_array($this->geom)) {
            foreach ($this->geom as $polygon) {
                $buildingQuery = "SELECT * FROM fnc_getBufferPolygonBuildings(ST_GeomFromText('" . $polygon . "', 4326), $bufferDisancePolygon);";

                $results = DB::select($buildingQuery); // Execute the query
            
               // Merge the results into $allBuildingResults
               $allBuildingResults = array_merge($allBuildingResults, $results);
            }
            $buildingResults = [];

            foreach ($allBuildingResults as $building) {
                $type = $building->structype;
    
                if (!isset($buildingResults[$type])) {
                    // Store the first occurrence as an object
                    $buildingResults[$type] = clone $building; 
                } else {
                    // Aggregate numeric fields
                    foreach ($building as $key => $value) {
                        if ($key !== 'structype' && is_numeric($value)) {
                            $buildingResults[$type]->$key += $value;
                        }
                    }
                }
            }
    
            $buildingResults = array_values($buildingResults);
        } else {
            // If $this->geom is a single string
            $buildingQuery = "SELECT * FROM fnc_getBufferPolygonBuildings(ST_GeomFromText('" . $this->geom . "', 4326), $bufferDisancePolygon);";
            $buildingResults = DB::select($buildingQuery);
        }

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