<?php

namespace App\Console\Commands;

use App\Services\PiiEncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class VerifyOwnerPiiEncryption extends Command
{
    protected $signature = 'pii:verify-owner-encryption
        {--chunk=100 : Number of owner rows checked at a time}';

    protected $description = 'Verify that owner PII is encrypted and decryptable without printing values';

    private const FIELDS = [
        'owner_name',
        'owner_gender',
        'owner_contact',
        'nid',
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

        DB::table('building_info.owners')
            ->select(array_merge(['id'], self::FIELDS))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($owners) use (
                $encryption,
                &$scannedRows,
                &$encryptedFields,
                &$plaintextFields,
                &$invalidFields
            ) {
                foreach ($owners as $owner) {
                    $scannedRows++;

                    foreach (self::FIELDS as $field) {
                        $value = $owner->{$field};

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
            $this->error(
                'Verification failed. No values were printed. Do not disable legacy plaintext support.'
            );

            return 1;
        }

        $this->info('Verification passed: all non-null owner PII fields are encrypted and decryptable.');

        return 0;
    }
}
