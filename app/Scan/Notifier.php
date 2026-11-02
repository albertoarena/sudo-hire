<?php

namespace App\Scan;

/**
 * Shows the summary notification. AppServiceProvider binds NativeNotifier inside
 * the app and MacNotifier (osascript) outside it.
 */
interface Notifier
{
    /**
     * @throws NotificationFailed
     */
    public function send(string $text): void;
}
