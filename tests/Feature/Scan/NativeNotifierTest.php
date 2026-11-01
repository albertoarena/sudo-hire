<?php

use App\Scan\NativeNotifier;
use App\Scan\NotificationFailed;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('posts the title, the message and the summary reference to the internal API', function () {
    Http::fake(['*/notification' => Http::response(['reference' => NativeNotifier::REFERENCE])]);

    app(NativeNotifier::class)->send('6 new jobs: 4 Laravel, 2 PHP');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/notification')
        && $request['title'] === 'Sudo Hire'
        && $request['body'] === '6 new jobs: 4 Laravel, 2 PHP'
        && $request['reference'] === 'sudo-hire-summary');
});

it('throws NotificationFailed when the internal API refuses the notification', function () {
    Http::fake(['*/notification' => Http::response(['error' => 'Unauthorized'], 403)]);

    app(NativeNotifier::class)->send('1 new job');
})->throws(NotificationFailed::class);

it('throws NotificationFailed when the internal API does not answer', function () {
    Http::fake(['*/notification' => Http::failedConnection('Connection refused')]);

    app(NativeNotifier::class)->send('1 new job');
})->throws(NotificationFailed::class, 'Connection refused');

it('fails a second notification that the internal API refuses after one it accepted', function () {
    Http::fake(['*/notification' => Http::sequence()
        ->push(['reference' => NativeNotifier::REFERENCE])
        ->push(['error' => 'Unauthorized'], 403)]);
    $notifier = app(NativeNotifier::class);

    $notifier->send('1 new job');
    $notifier->send('2 new jobs');
})->throws(NotificationFailed::class, 'the internal API did not accept it');
