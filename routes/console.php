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

// The app checks for updates when it starts. This checks again when the last check is
// one day old, so GitHub sees at most one check a day (docs/DECISIONS.md, "The updater").
Schedule::command('hire:check-updates')->hourly();
