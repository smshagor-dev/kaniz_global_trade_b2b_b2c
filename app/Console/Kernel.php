<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SyncB2BShipmentsCommand::class,
        \App\Console\Commands\SyncB2BFreightShipmentsCommand::class,
        \App\Console\Commands\CurrencySyncCommand::class,
        \App\Console\Commands\ProcessTradeFinanceCommand::class,
        \App\Console\Commands\ProcessB2BInsuranceCommand::class,
        \App\Console\Commands\SearchReindexCommand::class,
        \App\Console\Commands\SearchRetryFailedCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('b2b:shipments:sync')->everyFifteenMinutes();
        $schedule->command('b2b:freight:sync')->everyThirtyMinutes();
        $schedule->command('currency:sync')->everySixHours()->withoutOverlapping();
        $schedule->command('b2b:trade-finance:process')->everyThirtyMinutes()->withoutOverlapping();
        $schedule->command('b2b:insurance:process')->hourly()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
