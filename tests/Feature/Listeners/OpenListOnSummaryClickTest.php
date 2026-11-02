<?php

use App\Listeners\OpenListOnSummaryClick;
use App\Scan\NativeNotifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Native\Desktop\Events\Notifications\NotificationClicked;

beforeEach(function () {
    Http::fake();
});

it('listens for a click on a notification', function () {
    Event::fake();

    Event::assertListening(NotificationClicked::class, OpenListOnSummaryClick::class);
});

it('opens the menu bar list once for a click on the summary notification', function () {
    event(new NotificationClicked(NativeNotifier::REFERENCE, '{}'));

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), 'menu-bar/show'));
});

it('ignores a click on another notification', function () {
    event(new NotificationClicked('1730000000.abc1234', '{}'));

    Http::assertNothingSent();
});
