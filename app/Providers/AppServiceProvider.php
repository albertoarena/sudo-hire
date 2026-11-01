<?php

namespace App\Providers;

use App\Scan\MacNotifier;
use App\Scan\NativeNotifier;
use App\Scan\Notifier;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Native notifications inside the app. osascript from the terminal, cron or launchd.
        $this->app->bind(Notifier::class, fn (Application $app) => config('nativephp-internal.running')
            ? $app->make(NativeNotifier::class)
            : $app->make(MacNotifier::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
