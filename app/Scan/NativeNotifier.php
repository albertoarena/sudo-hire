<?php

namespace App\Scan;

use Native\Desktop\Notification;
use Throwable;

/**
 * Shows the summary as a native notification through the NativePHP internal API.
 * The fixed reference lets the click listener know the summary notification.
 * It gets the class behind the Notification facade, so the types are correct.
 */
final class NativeNotifier implements Notifier
{
    public const string TITLE = 'Sudo Hire';

    public const string REFERENCE = 'sudo-hire-summary';

    public function __construct(private readonly Notification $notification) {}

    public function send(string $text): void
    {
        try {
            $this->notification
                ->title(self::TITLE)
                ->message($text)
                ->reference(self::REFERENCE)
                ->show();
        } catch (Throwable $error) {
            throw new NotificationFailed("Native notification failed: {$error->getMessage()}", 0, $error);
        }

        // The internal API gives the reference back only when it shows the notification.
        if ($this->notification->reference !== self::REFERENCE) {
            throw new NotificationFailed('Native notification failed: the internal API did not accept it.');
        }
    }
}
