<?php

namespace App\Scan;

use Illuminate\Container\Attributes\Bind;

/**
 * Shows the summary notification. hire:scan uses osascript. M7 can bind a native notifier.
 */
#[Bind(MacNotifier::class)]
interface Notifier
{
    /**
     * @throws NotificationFailed
     */
    public function send(string $text): void;
}
