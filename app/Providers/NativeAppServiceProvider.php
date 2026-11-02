<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\MenuBar;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Sudo Hire lives in the menu bar: no window and no dock icon on start.
     * The icon is the stamp mark, a template image that macOS tints (resources/icons/menu-bar-mark.svg).
     */
    public function boot(): void
    {
        MenuBar::create()
            ->icon(public_path('menuBarIconTemplate.png'))
            ->tooltip('Sudo Hire')
            ->showDockIcon(false)
            ->width(400)
            ->height(640);
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
