<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The app runs schedule:run every minute while it runs, and it stops the previous
// schedule:run first. In the background, a long scan does not depend on it.
// hire:scan is isolated already, and it fetches only the sources that are due.
Schedule::command('hire:scan')->everyFiveMinutes()->runInBackground();
