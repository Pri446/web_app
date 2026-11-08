<?php

namespace App\Console\Commands;

use App\Services\PiiEncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EncryptExistingPropertyTaxOwnerPii extends Command
{
    protected $signature = 'pii:encrypt-existing-property-tax
        {--dry-run : Count plaintext values without changing the database}
        {--chunk=100 : Number of rows processed per transaction}';

    protected $description = 'Encrypt owner PII in taxpayment_info.tax_payments safely';

    private const FIELDS = ['owner_name', 'owner_contact'];

    public function handle(PiiEncryptionService $encryption): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');
        if ($chunkSize < 1 || $chunkSize > 1000) {
            throw new InvalidArgumentException('The chunk size must be between 1 and 1000.');
        }

        $scannedRows = 0;
        $affectedRows = 0;
        $plaintextFields = 0;

        DB::table('taxpayment_info.tax_payments')
            ->select(array_merge(['id'], self::FIELDS))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($records) use (
                $encryption,
                $dryRun,
                &$scannedRows,
                &$affectedRows,
                &$plaintextFields
            ) {
                $changes = [];
                foreach ($records as $record) {
                    $scannedRows++;
                    $updates = [];
                    foreach (self::FIELDS as $field) {
                        $value = $record->{$field};
                        if ($value === null || $encryption->isEncrypted($value)) {
                            continue;
                        }
                        $plaintextFields++;
                        if (!$dryRun) {
                            $updates[$field] = $encryption->encrypt((string) $value);
                        }
                    }
                    if ($updates !== []) {
                        $changes[$record->id] = $updates;
                    }
                }

                if ($changes !== []) {
                    DB::transaction(function () use ($changes, &$affectedRows) {
                        foreach ($changes as $id => $updates) {
                            DB::table('taxpayment_info.tax_payments')->where('id', $id)->update($updates);
                            $affectedRows++;
                        }
                    });
                }
            }, 'id');

        $this->table(
            ['Mode', 'Rows scanned', 'Rows changed', 'Plaintext fields found'],
            [[
                $dryRun ? 'DRY RUN' : 'WRITE',
                $scannedRows,
                $affectedRows,
                $plaintextFields,
            ]]
        );

        if ($dryRun) {
            $this->warn('Dry run only: no database values were changed.');
        } else {
            // Rebuild the derived status table so it contains the encrypted source values.
            DB::statement('select taxpayment_info.fnc_taxpaymentstatus()');
            $this->info('Property Tax owner PII backfill completed and status table rebuilt.');
        }

        return 0;
    }
}
