<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('shift:cron')
        // ->everyMinute();
        // $schedule->command('ws:listen')
        // ->everyMinute();
        $schedule->command('dailydatausage:run')->dailyAt('22:00');
        $schedule->command('updatekpidata:run')->dailyAt('08:42');
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    // protected function commands()
    // {
    //     $this->load(__DIR__.'/Commands');

    //     require base_path('routes/console.php');
    // }
    protected $commands = [
        // \App\Console\Commands\WebSocketListener::class,
        \App\Console\Commands\DailyDataUsageCommand::class,
        \App\Console\Commands\UpdateKPIDataChart::class,
    ];
}
