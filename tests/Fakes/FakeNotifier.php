<?php

namespace Tests\Fakes;

use App\Scan\NotificationFailed;
use App\Scan\Notifier;

/**
 * A notifier that keeps the texts in memory, for tests of the scan.
 */
final class FakeNotifier implements Notifier
{
    /** @var list<string> */
    public array $sent = [];

    public bool $fails = false;

    public function send(string $text): void
    {
        if ($this->fails) {
            throw new NotificationFailed('osascript failed.');
        }

        $this->sent[] = $text;
    }
}
