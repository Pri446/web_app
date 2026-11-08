<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ValidateSeedUserConfiguration::class,
        \App\Console\Commands\KpiCron::class,

        // update table counts
        \App\Console\Commands\GridsnWardpl_whenApplicationCron::class,
        \App\Console\Commands\GridsnWardpl_whenBuildingCron::class,
        \App\Console\Commands\GridsnWardpl_whenContainmentCron::class,
        \App\Console\Commands\GridsnWardpl_whenRoadlineCron::class,
        
        // Build functions and triggers
        \App\Console\Commands\GridsnWardpl_fncntgrCron::class,
        \App\Console\Commands\SwmPaymentFunctionBuild::class,
        \App\Console\Commands\TaxPaymentFunctionBuild::class,
        \App\Console\Commands\WaterSupplyFunctionBuild::class,
        \App\Console\Commands\MapTool_QryCron::class,
         \App\Console\Commands\CreateTopologyFunction::class,
    ];

    protected function schedule(Schedule $schedule)
    {
       
        // Schedule the command to run at midnight on January 1st
        $schedule->command('kpi:cron')->cron('0 0 1 1 *')->onSuccess(function () {
            Log::info('kpi:cron command executed at midnight on January 1st.');
        });
    }

    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
