<?php

namespace App\Listeners;

use App\List\SeenOnClose;
use App\List\UnseenBadge;
use Native\Desktop\Events\MenuBar\MenuBarHidden;

/**
 * Marks the New matches as seen when the menu bar list closes, then updates the badge.
 */
final class MarkListSeenOnClose
{
    public function __construct(
        private readonly SeenOnClose $seen,
        private readonly UnseenBadge $badge,
    ) {}

    public function handle(MenuBarHidden $event): void
    {
        $this->seen->closed();
        $this->badge->refresh();
    }
}
