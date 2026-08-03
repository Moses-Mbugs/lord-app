<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Moses was here
    // Finance manenos
    // Watches for the daily balances zip from 07:30, retrying up to 4 times (30 min apart)
    // until ~09:00. On success it imports and triggers the balances + top movers report
    // pipeline itself (replaces the old fixed 9:30 weekday run-balances job below).
    // Schedule::command('finance:import-daily-balances')
    // ->weekdays()
    // ->timezone('Africa/Nairobi')
    // ->at('7:30')
    // ->withoutOverlapping(180)           // prevent double-runs (lock for up to 3h)
    // ->runInBackground()
    // ->onFailure(function () {
    //     // Optional: send a Slack/email alert here
    //     // E.g: Notification::route('mail', config('reports.balances.ops_alert_email'))
    //     //          ->notify(new BalancesPipelineFailedNotification());
    // })
    // ->appendOutputTo(storage_path('logs/balances-pipeline.log'));

    // Prune customer_balances daily detail down to month-end snapshots overnight,
    // well clear of the 07:15 weekday balances import above.
    Schedule::command('finance:prune-balances')
        ->timezone('Africa/Nairobi')
        ->dailyAt('02:30')
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/prune-balances.log'));



    // Weekly deposit movement — build snapshot then email (every Friday at 12:00)
    Schedule::command('reports:build-weekly-segment')
        ->weeklyOn(5, '12:00')
        ->timezone('Africa/Nairobi')
        ->withoutOverlapping(60)
        ->appendOutputTo(storage_path('logs/weekly-segment.log'));

    Schedule::command('reports:email-weekly-segment')
        ->weeklyOn(5, '12:05')
        ->timezone('Africa/Nairobi')
        ->withoutOverlapping(60)
        ->appendOutputTo(storage_path('logs/weekly-segment.log'));

    // Weekly branch movers — build all three periods (Weekly/MTD/YTD) then email (every Friday at 12:10)
    // Schedule::command('reports:email-weekly-branch-movers --auto-build')
    //     ->weeklyOn(5, '12:10')
    //     ->timezone('Africa/Nairobi')
    //     ->withoutOverlapping(120)
    //     ->appendOutputTo(storage_path('logs/weekly-branch-movers.log'));
