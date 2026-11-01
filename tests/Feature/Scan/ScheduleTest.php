<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('runs hire:scan every 5 minutes in the background, with no overlap lock of its own', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event) => str_contains((string) $event->command, 'hire:scan'))
        ->values();

    expect($events)->toHaveCount(1)
        ->and($events[0]->expression)->toBe('*/5 * * * *')
        // The command is isolated already.
        ->and($events[0]->withoutOverlapping)->toBeFalse()
        // The app stops schedule:run every minute. The scan must not depend on it.
        ->and($events[0]->runInBackground)->toBeTrue();
});
