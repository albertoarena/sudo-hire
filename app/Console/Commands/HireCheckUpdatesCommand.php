<?php

namespace App\Console\Commands;

use App\Updates\UpdateChecks;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Native\Desktop\AutoUpdater;
use Throwable;

/**
 * Asks the updater to check GitHub when the last check is one day old. The updater then
 * downloads a new version and installs it when the user quits the app. It never restarts the app.
 */
#[Signature('hire:check-updates')]
#[Description('Check for a new version of the app, at most once a day')]
class HireCheckUpdatesCommand extends Command
{
    public function handle(UpdateChecks $checks, AutoUpdater $updater): int
    {
        if (! config('nativephp-internal.running') || ! config('nativephp.updater.enabled')) {
            return self::SUCCESS;
        }

        if (! $checks->isDue()) {
            return self::SUCCESS;
        }

        $checks->record();

        try {
            $updater->checkForUpdates();
        } catch (Throwable $error) {
            report($error);
        }

        return self::SUCCESS;
    }
}
