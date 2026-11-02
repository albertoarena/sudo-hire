<?php

namespace App\List;

use Illuminate\Support\Facades\Log;
use Native\Desktop\MenuBar\MenuBarManager;
use Throwable;

/**
 * The unseen count next to the menu bar icon: the unseen New matches, without the
 * "Location not stated" group. An empty label at 0. Only inside the app.
 */
final class UnseenBadge
{
    public function __construct(
        private readonly JobList $list,
        private readonly MenuBarManager $menuBar,
    ) {}

    public function refresh(): void
    {
        if (! config('nativephp-internal.running')) {
            return;
        }

        $unseen = $this->list->unseenCount();

        // A failed label must not stop a scan or a state change.
        try {
            $this->menuBar->label($unseen > 0 ? (string) $unseen : '');
        } catch (Throwable $error) {
            Log::warning("Menu bar label failed: {$error->getMessage()}");
        }
    }
}
