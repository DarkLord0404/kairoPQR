<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:health-check')->everyThreeHours();
Schedule::command('app:sync-meetings-to-cumple')->everyTenMinutes()->withoutOverlapping();
