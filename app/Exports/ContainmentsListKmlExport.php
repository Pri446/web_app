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

class ContainmentsListKmlExport implements
    FromQuery,
    WithChunkReading,
    WithBatchInserts,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithEvents
{
    private $binToPolygon = [];

    public function __construct(array $binPolygonMap)
    {
        foreach ($binPolygonMap as $item) {
            if (empty($item['bin'])) continue;

            $bin  = $item['bin'];
            $name = trim($item['polygon_name'] ?? 'Unnamed Polygon');

            if (!isset($this->binToPolygon[$bin])) {
                $this->binToPolygon[$bin] = $name;
            }
        }
    }

    /**
     * Build streaming DB query with microtime logging.
     */
    public function query()
    {
        $bins = array_keys($this->binToPolygon);

        if (empty($bins)) {
            return DB::table('fsm.containments')->whereRaw('1 = 0');
        }

        $query = DB::table('fsm.containments as c')
            ->join('building_info.build_contains as bc', function ($join) {
                $join->on('bc.containment_id', '=', 'c.id')
                    ->whereNull('bc.deleted_at')
                    ->whereNotNull('bc.bin')
                    ->whereNotNull('bc.containment_id');
            })
            ->join('building_info.buildings as b', function ($join) {
                $join->on('b.bin', '=', 'bc.bin')
                    ->whereNull('b.deleted_at');
            })
            ->leftJoin('fsm.containment_types as ct', 'ct.id', '=', 'c.type_id')
            ->whereIn('b.bin', $bins)
            ->whereNull('c.deleted_at')
            ->orderBy('c.id')
            ->select([
                'c.id',
                'c.type_id',
                'ct.type as containment_type',
                'c.location',
                'c.size',
                'c.pit_diameter',
                'c.tank_length',
                'c.tank_width',
                'c.depth',
                'c.septic_criteria',
                'c.construction_date',
                'c.emptied_status',
                'c.last_emptied_date',
                'c.next_emptying_date',
                'c.no_of_times_emptied',
                'c.surveyed_at',
                'c.toilet_count',
                'c.distance_closest_well',
                'b.bin',
                'b.ward',
                'b.house_locality',
                'b.house_number',
                'b.tax_code',
            ]);

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
     * Log progress during mapping (executed when chunk rows stream).
     */
    public function map($row): array
    {
        $polygonName = $this->binToPolygon[$row->bin] ?? 'KML Area';

        return [
            $polygonName,
            $row->id,
            $row->type_id,
            $row->containment_type,
            $row->location,
            $row->size,
            $row->pit_diameter,
            $row->tank_length,
            $row->tank_width,
            $row->depth,
            $row->septic_criteria,
            $row->construction_date,
            $row->emptied_status,
            $row->last_emptied_date,
            $row->next_emptying_date,
            $row->no_of_times_emptied,
            $row->surveyed_at,
            $row->toilet_count,
            $row->distance_closest_well,
            $row->bin,
            $row->ward,
            $row->house_locality,
            $row->house_number,
            $row->tax_code,
        ];
    }

    public function headings(): array
    {
        return [
            'Polygon Name',
            'Containment ID',
            'Containment Type ID',
            'Containment Type',
            'Location',
            'Size',
            'Pit Diameter',
            'Tank Length',
            'Tank Width',
            'Depth',
            'Septic Criteria',
            'Construction Date',
            'Emptied Status',
            'Last Emptied Date',
            'Next Emptying Date',
            'No. of Times Emptied',
            'Surveyed At',
            'Toilet Count',
            'Distance Closest Well',
            'BIN',
            'Ward',
            'House Locality',
            'House Number',
            'Tax Code',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:AA1')->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            },
        ];
    }

    public function title(): string
    {
        return 'Containment List';
    }
}
