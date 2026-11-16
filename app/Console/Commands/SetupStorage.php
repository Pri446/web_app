<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;

class SetupStorage extends Command
{
    protected $signature = 'storage:setup';

    protected $description = 'Create application storage directories and enforce 0777 directory permissions without overwriting files';

    public function handle(Filesystem $files): int
    {
        $directories = [
            storage_path(),
            storage_path('app'),
            storage_path('app/public'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
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

        $failed = false;

        foreach ($diskDirectories as $disk => $paths) {
            // Do not initialize the disk just to find its root: an adapter can
            // try to create that root before we have repaired its permissions.
            $root = config("filesystems.disks.{$disk}.root");
            if (config("filesystems.disks.{$disk}.driver") !== 'local' || !is_string($root) || trim($root) === '') {
                $this->error("Unable to prepare {$disk}: a local disk with a non-empty root is required.");
                $failed = true;
                continue;
            }
            if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|[/\\\\])~', $root)) {
                $root = base_path($root);
            }
            $directories[] = $root;
            foreach ($paths as $path) {
                $directories[] = $path === '' ? $root : rtrim($root, '/\\') . DIRECTORY_SEPARATOR . $path;
            }
        }

        // mkdir's mode is filtered by the process umask, even when passed 0777.
        $previousUmask = umask(0);
        try {
            foreach (array_unique($directories) as $directory) {
                try {
                    if (!$files->isDirectory($directory)) {
                        $files->ensureDirectoryExists($directory, 0777, true);
                    }
                    if (!$files->chmod($directory, 0777)) {
                        throw new \RuntimeException('Cannot apply 0777. Run setup as the directory owner or a privileged user, and check that the volume is writable.');
                    }
                    clearstatcache(true, $directory);
                    if (!$files->isDirectory($directory) || !$files->isWritable($directory)) {
                        throw new \RuntimeException('Directory does not exist or is not writable.');
                    }
                    if (DIRECTORY_SEPARATOR === '/' && (fileperms($directory) & 0777) !== 0777) {
                        throw new \RuntimeException('The filesystem did not retain 0777 directory permissions.');
                    }
                    $this->line('Ready (0777): ' . $directory);
                } catch (Throwable $exception) {
                    $failed = true;
                    $this->error('Unable to prepare ' . $directory . ': ' . $exception->getMessage());
                }
            }
        } finally {
            umask($previousUmask);
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('Storage directories are ready with 0777 permissions. Existing files were preserved.');
        $this->comment('Run php artisan storage:link to expose public uploads through public/storage.');

        return self::SUCCESS;
    }
}
