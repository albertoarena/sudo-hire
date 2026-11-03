<?php

namespace App\Providers;

use App\Settings\SettingsStore;
use App\Updates\UpdateChecks;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Sudo Hire lives in the menu bar: no dock icon, and no window on start once a country is stored.
     * Until then, the first run opens: closing it early stores nothing, so it opens again at the next start.
     * The icon is the stamp mark, a template image that macOS tints (resources/icons/menu-bar-mark.svg).
     * NativePHP checks for updates on every start, so the start counts as the update check of the day.
     */
    public function boot(): void
    {
        app(UpdateChecks::class)->record();

        MenuBar::create()
            ->icon(public_path('menuBarIconTemplate.png'))
            ->tooltip('Sudo Hire')
            ->showDockIcon(false)
            ->width(400)
            ->height(640);

        if (app(SettingsStore::class)->load()->country === null) {
            Window::open('first-run')
                ->url(route('first-run'))
                ->title('Welcome to Sudo Hire')
                ->width(520)
                ->height(470)
                ->resizable(false)
                ->showDevTools(false);
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     *
     * @return array<string, string>
     */
    public function phpIni(): array
    {
        return [];
    }
}
