<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        \Illuminate\Console\KeyGenerateCommand::class,
        \App\Console\Commands\LoginRemainderForTwoWeek::class,
        \App\Console\Commands\TurnOffSnoozeAccount::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('weekly:remind-to-login')
            ->weekly();
        $schedule->command('account:turn-off-snooze')
            ->daily();
    }
}
