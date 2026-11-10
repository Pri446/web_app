<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Imports\TaxImport;
use Maatwebsite\Excel\Facades\Excel;

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
        
        \DB::statement('TRUNCATE TABLE taxpayment_info.tax_payments RESTART IDENTITY');
        \DB::statement('ALTER SEQUENCE IF exists taxpayment_info.tax_payments_id_seq RESTART WITH 1');
        // TaxImport constructs TaxPayment models, whose PII mutators encrypt
        // owner_name and owner_contact before the rows are written in batches.
        Excel::import(new TaxImport, $filePath);

        $end_time=time();
        
        //check larave.log file to confirm all the queries has run successfully
        \Log::info("Tax data imported from excel/csv file to database successfully on ". date("F j, Y, g:i a"));
        Log::info('Total execution time : '. ($end_time-$start_time).'seconds');

        $this->info('Tax data imported successfully in '. ($end_time-$start_time).'seconds');
        return Command::SUCCESS;
    }
}
