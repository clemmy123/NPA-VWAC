<?php

\Illuminate\Support\Facades\Schedule::command('jumuishi:sync-users')->everyFiveMinutes()->withoutOverlapping();

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
