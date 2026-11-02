<?php

namespace App\Listeners;

use App\List\SeenOnClose;
use Native\Desktop\Events\MenuBar\MenuBarShown;

/**
 * Stores the time the menu bar list opened, for seen on close.
 */
final class RememberListOpened
{
    public function __construct(private readonly SeenOnClose $seen) {}

    public function handle(MenuBarShown $event): void
    {
        $this->seen->opened();
    }
}
