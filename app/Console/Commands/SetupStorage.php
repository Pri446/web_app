<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SetupStorage extends Command
{
    protected $signature = 'storage:setup';

    protected $description = 'Create the application storage directories without overwriting existing files';

    public function handle(Filesystem $files): int
    {
        $directories = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        $diskDirectories = [
            'local' => [
                'cwis/accountability-documents',
                'cwis/responsibility-documents',
                'cwis/rpm-documents',
                'cwis/safety-documents',
                'cwis/sustainability-documents',
                'cwis/reset-backups',
                'cwis/result-backups',
            ],
            'public' => [
                'building-survey-kml',
                'contract_documents',
                'cwis/equity-documents',
                'emptyings/houses',
                'emptyings/receipts',
                'swmpayment',
                'taxpayment',
                'watersupply',
            ],
            'importtax' => [''],
            'importwatersupply' => [''],
            'importswmpayment' => [''],
        ];

        foreach ($diskDirectories as $disk => $paths) {
            foreach ($paths as $path) {
                $directories[] = Storage::disk($disk)->path($path);
            }
        }

        $failed = false;

        foreach (array_unique($directories) as $directory) {
            try {
                if (!$files->isDirectory($directory)) {
                    $files->ensureDirectoryExists($directory, 0755, true);
                }

                if (!$files->isDirectory($directory) || !$files->isWritable($directory)) {
                    throw new \RuntimeException('Directory does not exist or is not writable.');
                }

                $this->line('Ready: ' . $directory);
            } catch (Throwable $exception) {
                $failed = true;
                $this->error('Unable to prepare ' . $directory . ': ' . $exception->getMessage());
            }
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('Storage directories are ready.');
        $this->comment('Run php artisan storage:link to expose public uploads through public/storage.');

        return self::SUCCESS;
    }
}
