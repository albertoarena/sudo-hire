<?php

namespace App\Listeners;

use App\Scan\NativeNotifier;
use Native\Desktop\Events\Notifications\NotificationClicked;
use Native\Desktop\Facades\MenuBar;

/**
 * A click on the summary notification opens the menu bar list (REQUIREMENTS section 10).
 */
final class OpenListOnSummaryClick
{
    public function handle(NotificationClicked $event): void
    {
        if ($event->reference !== NativeNotifier::REFERENCE) {
            return;
        }

        MenuBar::show();
    }
}
