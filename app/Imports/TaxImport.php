<?php

namespace App\Imports;

use App\Models\TaxPaymentInfo\TaxPayment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\RemembersChunkOffset; 

// The caller rebuilds map status after all rows finish, in the same transaction.
class TaxImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, WithColumnFormatting, WithMapping, WithBatchInserts
{
    use Importable;
    use RemembersChunkOffset;

    public function model(array $row)
    { 
        $chunkOffset = $this->getChunkOffset();
        return new TaxPayment([
            "tax_code" => $row['tax_code'],
            "owner_name" => $row['owner_name']?$row['owner_name']:null,
            "owner_contact" => $row['owner_contact']?$row['owner_contact']:null,
            #"last_payment_date" => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['last_payment_date']),
            "last_payment_date" => ($row['last_payment_date']),
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
    /**
    * @return array
    */
    public function rules(): array {
       
         return [
            'tax_code' => [
                'required',
                'string',
                //'unique:pgsql.taxpayment_info.tax_payments,tax_code',
            ],
            'owner_name' => [
                'nullable',
                'string',
            ],
            'owner_contact' => [
                'nullable',
                'string',
                'max:100',
            ],
            'last_payment_date' => [
                 'required',
                'date_format:Y-m-d', 
               
                ],
            
        ];
    }
    public function map($row): array
    {
        return [
           "tax_code" => trim((string) $row['tax_code']),
            "owner_name" => $row['owner_name'],
            "owner_contact" => isset($row['owner_contact']) ? (string) $row['owner_contact'] : null,
            "last_payment_date" => $this->normalizeDate($row['last_payment_date'] ?? ''),
        ];
    }
     /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'last_payment_date' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }


    private function normalizeDate($value): string
    {
        $value = trim((string) $value);
        foreach (['Y-m-d', 'n/j/Y'] as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($parsed && (!$errors || (!$errors['warning_count'] && !$errors['error_count']))) {
                return $parsed->format('Y-m-d');
            }
        }
        return $value; // Let validation report the row instead of creating 1970-01-01.
    }
       
}
