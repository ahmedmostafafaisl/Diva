<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
       $schedule->command('sync:products')->hourly();

        $schedule->command('sync:products')->cron('0 */10 * * *');
        $schedule->command('woocommerce:fetch-shippings')->dailyAt('03:00');
        $schedule->command('sync:users')->dailyAt('05:00');
    }

    protected $commands = [

        \App\Console\Commands\SyncProducts::class,
        \App\Console\Commands\SyncUsers::class,

    ];


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
