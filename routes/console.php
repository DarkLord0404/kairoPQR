<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:health-check')->everyThreeHours()->withoutOverlapping();
Schedule::command('app:health-check --full')->dailyAt('05:15')->withoutOverlapping();
Schedule::command('app:sync-meetings-to-cumple')->everyTenMinutes()->withoutOverlapping();
Schedule::command('app:cleanup-audio')->dailyAt('02:30')->withoutOverlapping();
