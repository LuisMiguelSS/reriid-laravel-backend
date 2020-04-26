<?php

namespace App\Console;

use App\User;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Hard delete users that have been softdeleted in >=30 days
        // Daily Execution
        $schedule->call(function () {

            // Get users
            $soft_deleted_users = User::whereNotNull('deleted_at')->where(
                'deleted_at', '<=', now()->subDays(30)->toDateTimeString()
            )->get();

            // Delete them
            $soft_deleted_users->each->forceDelete();
            
        })->daily();
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
