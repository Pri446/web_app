<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class BuildingsListKmlExport implements
    FromQuery,
    WithChunkReading,
    WithBatchInserts,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithEvents
{
    /**
     * bin => polygon_name (first polygon only)
     *
     * @var array<string,string>
     */
    private $binToPolygon = [];

    /**
     * @param array $binPolygonMap Each item: ['bin' => '...', 'polygon_name' => '...']
     */
    public function __construct(array $binPolygonMap)
    {
        foreach ($binPolygonMap as $item) {
            if (!empty($item['bin'])) {
                $bin   = $item['bin'];
                $name  = trim($item['polygon_name'] ?? 'Unnamed Polygon');

                // keep only first polygon per BIN
                if (!isset($this->binToPolygon[$bin])) {
                    $this->binToPolygon[$bin] = $name;
                }
            }
        }
    }

    /**
     * Build the streaming DB query with microtime measurement.
     */
    public function query()
{
    $bins = array_keys($this->binToPolygon);

    if (empty($bins)) {
       
        return DB::table('building_info.buildings')->whereRaw('1 = 0');
    }

    // Aggregate owners to one row per BIN (pick any logic: MAX, MIN, string_agg, etc.)
    $ownersSub = DB::table('building_info.owners')
        ->select([
            'bin',
            DB::raw("MAX(CASE WHEN owner_name IS NULL OR owner_name = '' THEN NULL ELSE '********' END) AS owner_name"),
            DB::raw("MAX(CASE WHEN owner_gender IS NULL OR owner_gender = '' THEN NULL ELSE '********' END) AS owner_gender"),
            DB::raw("MAX(CASE WHEN owner_contact IS NULL OR owner_contact = '' THEN NULL ELSE '********' END) AS owner_contact"),
            DB::raw("MAX(CASE WHEN nid IS NULL OR nid = '' THEN NULL ELSE '********' END) AS nid"),
        ])
        ->groupBy('bin');

    // Aggregate toilets to one row per BIN 
    $btSub = DB::table('fsm.build_toilets')
        ->select([
            'bin',
            DB::raw('MAX(toilet_id) AS toilet_id'),
        ])
        ->whereNull('deleted_at')
        ->groupBy('bin');

    $query = DB::table('building_info.buildings as b')
        ->select([
            'b.bin',
            'b.tax_code',
            'b.house_number',
            'b.house_locality',
            'b.ward',
            'b.road_code',
            'st.type as structure_type',
            'b.floor_count',
            'b.construction_year',
            'b.household_served',
            'b.population_served',
            'b.surveyed_date',
            'f.name as functional_use_id',
            'u.name as use_category_id',
            'b.office_business_name',
            's.source as water_source',
            'b.building_associated_to',
            'b.well_presence_status',
            'b.distance_from_well',
            'b.toilet_status',
            'b.toilet_count',
            'b.household_with_private_toilet',
            'b.population_with_private_toilet',
            'ss.sanitation_system as sanitation_system',
            'b.sewer_code',
            'b.drain_code',
            'b.desludging_vehicle_accessible',
            'b.swm_customer_id',
            'b.water_customer_id',
            'b.estimated_area',
            'b.male_population',
            'b.female_population',
            'b.other_population',
            'b.diff_abled_male_pop',
            'b.diff_abled_female_pop',
            'b.diff_abled_others_pop',
            'b.verification_status',
            'owners.owner_name',
            'owners.owner_gender',
            'owners.owner_contact',
            'owners.nid',
            'lic.community_name',
            'b.low_income_hh',
            'b.watersupply_pipe_code',
            'bt.toilet_id',
            't.name as toilet_name',
        ])
        ->leftJoin('building_info.structure_types as st', 'b.structure_type_id', '=', 'st.id')
        ->leftJoin('building_info.functional_uses as f', 'b.functional_use_id', '=', 'f.id')
        ->leftJoin('building_info.use_categorys as u', 'b.use_category_id', '=', 'u.id')
        ->leftJoin('building_info.sanitation_systems as ss', 'b.sanitation_system_id', '=', 'ss.id')
        ->leftJoin('building_info.water_sources as s', 'b.water_source_id', '=', 's.id')
        ->leftJoin('layer_info.low_income_communities as lic', 'lic.id', '=', 'b.lic_id')

        ->leftJoinSub($btSub, 'bt', function ($join) {
            $join->on('bt.bin', '=', 'b.bin');
        })
        ->leftJoin('fsm.toilets as t', 'bt.toilet_id', '=', 't.id')

        ->leftJoinSub($ownersSub, 'owners', function ($join) {
            $join->on('owners.bin', '=', 'b.bin');
        })

        ->whereNull('b.deleted_at')
        ->whereIn('b.bin', $bins)
        ->orderBy('b.bin', 'asc');

    return $query;
}


    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    /**
     * Headings for the Excel sheet.
     */
    public function headings(): array
    {
        return [
            'Polygon Name',
            'BIN',
            'Tax Code',
            'House Number',
            'House Locality',
            'Ward',
            'Road Code',
            'Structure Type',
            'Floor Count',
            'Construction Year',
            'Household Served',
            'Population Served',
            'Surveyed Date',
            'Functional Use',
            'Use Category',
            'Office / Business Name',
            'Water Source',
            'Building Associated To',
            'Well Presence Status',
            'Distance From Well',
            'Toilet Status',
            'Toilet Count',
            'Household With Private Toilet',
            'Population With Private Toilet',
            'Sanitation System',
            'Sewer Code',
            'Drain Code',
            'Desludging Vehicle Accessible',
            'SWM Customer ID',
            'Water Customer ID',
            'Estimated Area',
            'Male Population',
            'Female Population',
            'Other Population',
            'Diff Abled Male Population',
            'Diff Abled Female Population',
            'Diff Abled Others Population',
            'Verification Status',
            'Owner Name',
            'Owner Gender',
            'Owner Contact',
            'Owner NID',
            'Community Name',
            'Low Income Household',
            'Water Supply Pipe Code',
            'Toilet ID',
            'Toilet Name',
        ];
    }

    /**
     * Log progress while mapping rows.
     */
    public function map($row): array
    {
        $polygonName = $this->binToPolygon[$row->bin] ?? 'KML Area';

        return [
            $polygonName,
            $row->bin,
            $row->tax_code,
            $row->house_number,
            $row->house_locality,
            $row->ward,
            $row->road_code,
            $row->structure_type,
            $row->floor_count,
            $row->construction_year,
            $row->household_served,
            $row->population_served,
            $row->surveyed_date,
            $row->functional_use_id,
            $row->use_category_id,
            $row->office_business_name,
            $row->water_source,
            $row->building_associated_to,
            $row->well_presence_status,
            $row->distance_from_well,
            $row->toilet_status,
            $row->toilet_count,
            $row->household_with_private_toilet,
            $row->population_with_private_toilet,
            $row->sanitation_system,
            $row->sewer_code,
            $row->drain_code,
            $row->desludging_vehicle_accessible,
            $row->swm_customer_id,
            $row->water_customer_id,
            $row->estimated_area,
            $row->male_population,
            $row->female_population,
            $row->other_population,
            $row->diff_abled_male_pop,
            $row->diff_abled_female_pop,
            $row->diff_abled_others_pop,
            $row->verification_status,
            ($row->owner_name === null || $row->owner_name === '') ? $row->owner_name : '********',
            ($row->owner_gender === null || $row->owner_gender === '') ? $row->owner_gender : '********',
            ($row->owner_contact === null || $row->owner_contact === '') ? $row->owner_contact : '********',
            ($row->nid === null || $row->nid === '') ? $row->nid : '********',
            $row->community_name,
            $row->low_income_hh,
            $row->watersupply_pipe_code,
            $row->toilet_id,
            $row->toilet_name,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:AV1')->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            },
        ];
    }

    public function title(): string
    {
        return 'Buildings List';
    }
}
