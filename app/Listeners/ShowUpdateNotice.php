<?php

namespace App\Listeners;

use Illuminate\Contracts\Cache\Repository;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;
use Native\Desktop\Notification;
use Throwable;

/**
 * When the updater has downloaded a new version, a native notification says that it
 * installs when the user quits the app. It shows once for each version.
 */
final class ShowUpdateNotice
{
    public const string REFERENCE = 'sudo-hire-update';

    public const string KEY = 'updates.noticed_version';

    public function __construct(
        private readonly Notification $notification,
        private readonly Repository $cache,
    ) {}

    public function handle(UpdateDownloaded $event): void
    {
        if ($this->cache->get(self::KEY) === $event->version) {
            return;
        }

        try {
            $this->notification
                ->title('Sudo Hire')
                ->message("Sudo Hire {$event->version} is ready. It installs when you quit Sudo Hire.")
                ->reference(self::REFERENCE)
                ->show();
        } catch (Throwable $error) {
            report($error);

            return;
        }

        // The internal API gives the reference back only when it shows the notification.
        if ($this->notification->reference === self::REFERENCE) {
            $this->cache->forever(self::KEY, $event->version);
        }
    }
}
