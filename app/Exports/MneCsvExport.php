<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Cwis\DataSource;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use DB;


class MneCsvExport implements FromView, WithEvents, WithColumnWidths, ShouldAutoSize
{
    private $year;

    public function __construct(int $year)
    {
        $this->year = $year;
    }

    public function view(): View
    {
        $years = $this->year;
    
        // Keep mapped indicators visible even when that country/year has no saved result.
        $indicators = app(\App\Services\Cwis\CwisIndicatorService::class);
        $country = $indicators->activeCountry();
        $saved = $indicators->resultsForCountry($years, $country);
        $results = $indicators->forCountry($country)->map(function ($indicator) use ($saved, $years) {
            $row = clone $indicator;
            $row->year = $years;
            $row->data_value = $saved->get($row->indicator_code)->data_value ?? null;
            return $row;
        });
        foreach ($results as $result) {
            $result->data_value = \App\Support\CwisValueFormatter::format($result->data_value);
        }
   
        return view('exports.CWIS_', compact('results', 'years'));
    }
    

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                // Wraptext
                $event->sheet->getStyle('B')->getAlignment()->setWrapText(true);
                $event->sheet->getStyle('C')->getAlignment()->setWrapText(true);

                //  row height
                $event->sheet->getRowDimension(1)->setRowHeight(40);
                $event->sheet->getRowDimension(2)->setRowHeight(25);
                $event->sheet->getRowDimension(3)->setRowHeight(20);
                $event->sheet->getRowDimension(4)->setRowHeight(25);
                // $event->sheet->getRowDimension(3)->setRowHeight(30);
                // $event->sheet->getRowDimension(4)->setRowHeight(15);
                //  cell merge
                $event->sheet->mergeCells('A1:C1');
                 $event->sheet->mergeCells('A2:C2');

                // $event->sheet->mergeCells('A2:D2');
                // font size
                $event->sheet->getDelegate()->getStyle('1')->getFont()->setSize(20);
                $event->sheet->getDelegate()->getStyle('2')->getFont()->setSize(22);
                $event->sheet->getDelegate()->getStyle('3')->getFont()->setSize(15);
                $event->sheet->getDelegate()->getStyle('4')->getFont()->setSize(15);


                // $event->sheet->getDelegate()->getStyle('3')->getFont()->setSize(16);
                // $event->sheet->getDelegate()->getStyle('4')->getFont()->setSize(12);


                $event->sheet->getStyle('A1:C1')->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => '538dd5'],]);
                $event->sheet->getStyle('A2:C2')->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => 'b8cce4'],]);
                $event->sheet->getStyle('A3:C3')->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => 'FFFFFF'],]);

                // $event->sheet->getStyle('A3:D3')->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => 'c5d9f1'],]);
                // $event->sheet->getStyle('A4:D4')->getFill()->applyFromArray(['fillType' => 'solid', 'rotation' => 0, 'color' => ['rgb' => 'E2ECF8'],]);

                // SET ALIGN
                $event->sheet->getDelegate()->getStyle('A1:C1')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $event->sheet->getDelegate()->getStyle('A2:C2')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('A1')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('A2:C2')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $event->sheet->getDelegate()->getStyle('A3:C3')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $event->sheet->getDelegate()->getStyle('A4:C4')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                // $event->sheet->getDelegate()->getStyle('D3')
                //     ->getAlignment()
                //     ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                // $event->sheet->getDelegate()->getStyle('A3:B3')
                //     ->getAlignment()
                //     ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                // set borders
                $event->sheet->getStyle('A1:C26')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                            'color' => ['argb' => '000000'],
                        ],

                    ],
                ]);
            }
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 20,
            'C' => 18,
        ];
    }

    public function drawings()

    {

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setPath(public_path('/img/logo-imis.jpg'));
        $drawing->setHeight(100);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(100);
        return $drawing;
    }
}
