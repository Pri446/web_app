<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class BuildingsKmlExport implements FromView, WithTitle, WithEvents
{
    /**
     * polygon_name => [bin1, bin2, ...]
     *
     * @var array<string, string[]>
     */
    private $polygonToBins = [];

    /**
     * @param array $binPolygonMap  Each item: ['bin' => '...', 'polygon_name' => '...']
     */
    public function __construct(array $binPolygonMap)
    {
        foreach ($binPolygonMap as $item) {
            $bin   = $item['bin'] ?? null;
            $name  = trim($item['polygon_name'] ?? 'Unnamed Polygon');

            if (!$bin) {
                continue;
            }

            if (!isset($this->polygonToBins[$name])) {
                $this->polygonToBins[$name] = [];
            }
            $this->polygonToBins[$name][] = $bin;
        }
    }

    public function view(): View
    {
        $allPolygonData = [];

        foreach ($this->polygonToBins as $polygonName => $bins) {
            $bins = array_values(array_unique($bins));
            if (empty($bins)) {
                continue;
            }

            // aggregate by structype for this polygon
            $buildingResults = DB::table('building_info.buildings as b')
                ->leftJoin('building_info.structure_types as st', 'b.structure_type_id', '=', 'st.id')
                ->leftJoin('building_info.sanitation_systems as ss', 'b.sanitation_system_id', '=', 'ss.id')
                ->whereIn('b.bin', $bins)
                ->whereNull('b.deleted_at')
                ->where('ss.map_display', true)
                ->groupBy('b.structure_type_id', 'st.id', 'st.type')
                ->orderBy('st.id')
                ->selectRaw("
                    st.type                               AS structype,
                    COUNT(*)::integer                     AS count,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 1)::integer  AS sewer_network,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 2)::integer  AS drain_network,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 3)::integer  AS septic_tank,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 4)::integer  AS pit_holding_tank,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 5)::integer  AS onsite_treatment,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 6)::integer  AS composting_toilet,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 7)::integer  AS water_body,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 8)::integer  AS open_ground,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 9)::integer  AS community_toilet,
                    COUNT(*) FILTER (WHERE b.sanitation_system_id = 10)::integer AS open_defacation,
                    SUM(COALESCE(b.population_served, 0))::integer       AS total_population,
                    SUM(COALESCE(b.household_served, 0))::integer        AS total_household_served
                ")
                ->get();

            $rows = [];
            $total                    = 0;
            $total_sewer_network      = 0;
            $total_drain_network      = 0;
            $total_septic_tank        = 0;
            $total_pit_holding_tank   = 0;
            $total_onsite_treatment   = 0;
            $total_composting_toilet  = 0;
            $total_water_body         = 0;
            $total_open_ground        = 0;
            $total_community_toilet   = 0;
            $total_open_defecation    = 0;
            $total_household_served   = 0;
            $total_population         = 0;

            foreach ($buildingResults as $building) {
                $total                   += $building->count;
                $total_sewer_network     += $building->sewer_network;
                $total_drain_network     += $building->drain_network;
                $total_septic_tank       += $building->septic_tank;
                $total_pit_holding_tank  += $building->pit_holding_tank;
                $total_onsite_treatment  += $building->onsite_treatment;
                $total_composting_toilet += $building->composting_toilet;
                $total_water_body        += $building->water_body;
                $total_open_ground       += $building->open_ground;
                $total_community_toilet  += $building->community_toilet;
                $total_open_defecation   += $building->open_defacation;
                $total_household_served  += $building->total_household_served;
                $total_population        += $building->total_population;

                $rows[] = [
                    'Structure Type'      => $building->structype,
                    'Buildings'          => $building->count,
                    'Sewer Network'      => $building->sewer_network,
                    'Drain Network'      => $building->drain_network,
                    'Septic Tank'        => $building->septic_tank,
                    'Pit / Holding Tank' => $building->pit_holding_tank,
                    'Onsite Treatment'   => $building->onsite_treatment,
                    'Composting Toilet'  => $building->composting_toilet,
                    'Water Body'         => $building->water_body,
                    'Open Ground'        => $building->open_ground,
                    'Community Toilet'   => $building->community_toilet,
                    'Open Defecation'    => $building->open_defacation,
                    'Household Served'   => $building->total_household_served,
                    'Population Served'  => $building->total_population,
                ];
            }

            $rows[] = [
                'StructureType'      => 'Total',
                'Buildings'          => $total,
                'Sewer Network'      => $total_sewer_network,
                'Drain Network'      => $total_drain_network,
                'Septic Tank'        => $total_septic_tank,
                'Pit / Holding Tank' => $total_pit_holding_tank,
                'Onsite Treatment'   => $total_onsite_treatment,
                'Composting Toilet'  => $total_composting_toilet,
                'Water Body'         => $total_water_body,
                'Open Ground'        => $total_open_ground,
                'Community Toilet'   => $total_community_toilet,
                'Open Defecation'    => $total_open_defecation,
                'Household Served'   => $total_household_served,
                'Population Served'  => $total_population,
            ];

            $allPolygonData[] = [
                'polygon_name' => $polygonName,
                'rows'         => $rows,
            ];
        }

        return view('exports.kml-buildings', [
            'allPolygonData' => $allPolygonData,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:O1')->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            },
        ];
    }

    public function title(): string
    {
        return 'Buildings Sheet';
    }
}
