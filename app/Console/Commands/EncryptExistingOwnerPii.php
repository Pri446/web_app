<?php

namespace App\Console\Commands;

use App\Services\PiiEncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EncryptExistingOwnerPii extends Command
{
    protected $signature = 'pii:encrypt-existing-owners
        {--dry-run : Count plaintext values without changing the database}
        {--chunk=100 : Number of owner rows processed per transaction}';

    protected $description = 'Encrypt legacy plaintext values in building_info.owners safely';

    private const FIELDS = [
        'owner_name',
        'owner_gender',
        'owner_contact',
        'nid',
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

        DB::table('building_info.owners')
            ->select(array_merge(['id'], self::FIELDS))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($owners) use (
                $encryption,
                $dryRun,
                &$scannedRows,
                &$affectedRows,
                &$plaintextFields
            ) {
                $changes = [];

                foreach ($owners as $owner) {
                    $scannedRows++;
                    $updates = [];

                    foreach (self::FIELDS as $field) {
                        $value = $owner->{$field};

                        if ($value === null || $encryption->isEncrypted($value)) {
                            continue;
                        }

                        $plaintextFields++;

                        if (!$dryRun) {
                            $updates[$field] = $encryption->encrypt((string) $value);
                        }
                    }

                    if ($updates !== []) {
                        $changes[$owner->id] = $updates;
                    }
                }

                if ($changes !== []) {
                    DB::transaction(function () use ($changes, &$affectedRows) {
                        foreach ($changes as $ownerId => $updates) {
                            DB::table('building_info.owners')
                                ->where('id', $ownerId)
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

        if ($dryRun) {
            $this->warn('Dry run only: no database values were changed.');
        } else {
            $this->info('Owner PII backfill completed. Run pii:verify-owner-encryption next.');
        }

        return 0;
    }
}
