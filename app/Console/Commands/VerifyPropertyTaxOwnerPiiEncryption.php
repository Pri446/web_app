<?php

namespace App\Console\Commands;

use App\Services\PiiEncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class VerifyPropertyTaxOwnerPiiEncryption extends Command
{
    protected $signature = 'pii:verify-property-tax-encryption
        {--chunk=100 : Number of rows checked at a time}';

    protected $description = 'Verify Property Tax owner PII without displaying values';

    public function handle(PiiEncryptionService $encryption): int
    {
        $chunkSize = (int) $this->option('chunk');
        if ($chunkSize < 1 || $chunkSize > 1000) {
            throw new InvalidArgumentException('The chunk size must be between 1 and 1000.');
        }

        $scannedRows = 0;
        $encryptedFields = 0;
        $plaintextFields = 0;
        $invalidFields = 0;

        DB::table('taxpayment_info.tax_payments')
            ->select(['id', 'owner_name', 'owner_contact'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($records) use (
                $encryption,
                &$scannedRows,
                &$encryptedFields,
                &$plaintextFields,
                &$invalidFields
            ) {
                foreach ($records as $record) {
                    $scannedRows++;
                    foreach (['owner_name', 'owner_contact'] as $field) {
                        $value = $record->{$field};
                        if ($value === null) {
                            continue;
                        }
                        if (!$encryption->isEncrypted($value)) {
                            $plaintextFields++;
                            continue;
                        }
                        try {
                            $encryption->decrypt($value);
                            $encryptedFields++;
                        } catch (Throwable $exception) {
                            $invalidFields++;
                        }
                    }
                }
            }, 'id');

        $this->table(
            ['Rows scanned', 'Encrypted fields', 'Plaintext fields', 'Invalid fields'],
            [[$scannedRows, $encryptedFields, $plaintextFields, $invalidFields]]
        );

        if ($plaintextFields > 0 || $invalidFields > 0) {
            $this->error('Verification failed. No Property Tax owner PII values were printed.');
            return 1;
        }

        $this->info('Verification passed: all non-null Property Tax owner PII fields are encrypted and decryptable.');
        return 0;
    }
}
