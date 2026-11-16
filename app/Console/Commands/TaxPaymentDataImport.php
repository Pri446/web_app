<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TaxPaymentDataImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:tax';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import of Tax data from excel/csv file to database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $start_time=time();
        // Flysystem v3 requires a directory argument for listContents() and
        // returns DirectoryListing objects rather than the old array format.
        // The scheduled import needs one predictable file, so address the same
        // filename used by the web upload instead of choosing the first entry
        // from a directory scan.
        $filename = 'building-tax-payments.csv';
        $disk = Storage::disk('importtax');

        if (!$disk->exists($filename)) {
            $this->error("Tax import file was not found: {$filename}");

            return Command::FAILURE;
        }

        // path() supplies the correct platform-specific absolute path. Keeping
        // Windows backslashes intact prevents malformed paths such as E:folder.
        $filePath = $disk->path($filename);
        
        try {
            $result = app(\App\Services\TaxPaymentImportService::class)->import($filePath);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            foreach (array_slice($e->failures(), 0, 20) as $failure) {
                $this->error('CSV row ' . $failure->row() . ': invalid ' . $failure->attribute() . '.');
            }
            $this->error('Import cancelled. Previous data was preserved.');
            return Command::FAILURE;
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) $this->error($message);
            }
            return Command::FAILURE;
        } catch (\Throwable $e) {
            Log::error('Property Tax command import failed.', ['exception_type' => get_class($e)]);
            $this->error('Import failed. Previous data was preserved. Check database functions and PII encryption configuration.');
            return Command::FAILURE;
        }
        $this->info("Imported {$result['total']} records: {$result['matched']} matched; {$result['unmatched']} unmatched.");
        $end_time=time();
        
        //check larave.log file to confirm all the queries has run successfully
        \Log::info("Tax data imported from excel/csv file to database successfully on ". date("F j, Y, g:i a"));
        Log::info('Total execution time : '. ($end_time-$start_time).'seconds');

        $this->info('Tax data imported successfully in '. ($end_time-$start_time).'seconds');
        return Command::SUCCESS;
    }
}
