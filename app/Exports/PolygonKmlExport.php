<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class PolygonKmlExport implements FromView, WithTitle, WithEvents
{
    /**
     * @var array
     */
    private array $polygonAreas;

    /**
     * @param array $polygonAreas
     * [
     *   ['polygon_name' => string, 'area_km2' => float]
     * ]
     */
    public function __construct(array $polygonAreas)
    {
        $this->polygonAreas = $polygonAreas;
    }

    /**
     * Generate the view for export
     */
    public function view(): View
    {
        return view('exports.polygon-area', [
            'polygonAreas' => $this->polygonAreas,
        ]);
    }

    /**
     * Excel sheet title
     */
    public function title(): string
    {
        return __('Polygon Area (km²)');
    }

    /**
     * Excel styling
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:B1')->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            },
        ];
    }
}
