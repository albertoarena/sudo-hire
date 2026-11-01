<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The app runs schedule:run every minute while it runs. hire:scan is isolated
// already, and it fetches only the sources that are due.
Schedule::command('hire:scan')->everyFiveMinutes();
