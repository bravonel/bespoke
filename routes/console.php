<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('activity:expire-sessions')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('activity:detect-anomalies')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('activity:verify-chain')->dailyAt('02:10')->withoutOverlapping();
Schedule::command('activity:prune')->dailyAt('02:30')->withoutOverlapping();
