<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class BuildContainKmlExport implements
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
            if (empty($item['bin'])) {
                continue;
            }

            $bin  = $item['bin'];
            $name = trim($item['polygon_name'] ?? 'Unnamed Polygon');

            if (!isset($this->binToPolygon[$bin])) {
                $this->binToPolygon[$bin] = $name;
            }
        }
    }

    public function query()
    {
        $bins = array_keys($this->binToPolygon);

        if (empty($bins)) {
            return DB::table('building_info.build_contains')->whereRaw('1 = 0');
        }

        return DB::table('building_info.build_contains as bc')
            ->join('building_info.buildings as b', function ($join) {
                $join->on('b.bin', '=', 'bc.bin')
                     ->whereNull('b.deleted_at');
            })
            ->whereIn('bc.bin', $bins)
            ->whereNull('bc.deleted_at')
            ->orderBy('bc.bin', 'asc')
            ->select([
                'bc.bin',
                'bc.containment_id',
            ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Polygon Name',
            'BIN',
            'Containment ID',
        ];
    }

    public function map($row): array
    {
        $polygonName = $this->binToPolygon[$row->bin] ?? 'KML Area';

        return [
            $polygonName,
            $row->bin,
            $row->containment_id,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:C1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);
            },
        ];
    }

    public function title(): string
    {
        return 'Build-Contain List';
    }
}
