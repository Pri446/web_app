<?php

namespace App\Console\Commands;

use App\Services\PiiEncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class VerifyApplicationCustomerPiiEncryption extends Command
{
    protected $signature = 'pii:verify-application-encryption
        {--chunk=100 : Number of application rows checked at a time}';

    protected $description = 'Verify Application customer PII without displaying values';

    private const FIELDS = [
        'customer_name',
        'customer_gender',
        'customer_contact',
    ];

    public function handle(PiiEncryptionService $encryption): int
    {
        $chunkSize = (int) $this->option('chunk');

        if ($chunkSize < 1 || $chunkSize > 1000) {
            throw new InvalidArgumentException(
                'The chunk size must be between 1 and 1000.'
            );
        }

        $scannedRows = 0;
        $encryptedFields = 0;
        $plaintextFields = 0;
        $invalidFields = 0;

        DB::table('fsm.applications')
            ->select(array_merge(['id'], self::FIELDS))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($applications) use (
                $encryption,
                &$scannedRows,
                &$encryptedFields,
                &$plaintextFields,
                &$invalidFields
            ) {
                foreach ($applications as $application) {
                    $scannedRows++;

                    foreach (self::FIELDS as $field) {
                        $value = $application->{$field};

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
            $this->error('Verification failed. No customer PII values were printed.');

            return 1;
        }

        $this->info('Verification passed: all non-null Application customer PII fields are encrypted and decryptable.');

        return 0;
    }
}
