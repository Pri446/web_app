<?php

namespace App\Console\Commands;

use App\Services\PiiEncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EncryptExistingApplicationCustomerPii extends Command
{
    protected $signature = 'pii:encrypt-existing-applications
        {--dry-run : Count plaintext values without changing the database}
        {--chunk=100 : Number of application rows processed per transaction}';

    protected $description = 'Encrypt customer PII in fsm.applications safely';

    private const FIELDS = [
        'customer_name',
        'customer_gender',
        'customer_contact',
    ];

    public function handle(PiiEncryptionService $encryption): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        if ($chunkSize < 1 || $chunkSize > 1000) {
            throw new InvalidArgumentException(
                'The chunk size must be between 1 and 1000.'
            );
        }

        $scannedRows = 0;
        $affectedRows = 0;
        $plaintextFields = 0;

        DB::table('fsm.applications')
            ->select(array_merge(['id'], self::FIELDS))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($applications) use (
                $encryption,
                $dryRun,
                &$scannedRows,
                &$affectedRows,
                &$plaintextFields
            ) {
                $changes = [];

                foreach ($applications as $application) {
                    $scannedRows++;
                    $updates = [];

                    foreach (self::FIELDS as $field) {
                        $value = $application->{$field};

                        if ($value === null || $encryption->isEncrypted($value)) {
                            continue;
                        }

                        $plaintextFields++;

                        if (!$dryRun) {
                            $updates[$field] = $encryption->encrypt((string) $value);
                        }
                    }

                    if ($updates !== []) {
                        $changes[$application->id] = $updates;
                    }
                }

                if ($changes !== []) {
                    DB::transaction(function () use ($changes, &$affectedRows) {
                        foreach ($changes as $applicationId => $updates) {
                            // Query builder avoids model mutators during a controlled backfill.
                            DB::table('fsm.applications')
                                ->where('id', $applicationId)
                                ->update($updates);
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

        $dryRun
            ? $this->warn('Dry run only: no database values were changed.')
            : $this->info('Application customer PII backfill completed. Run pii:verify-application-encryption next.');

        return 0;
    }
}
