<?php

use App\Listeners\ShowUpdateNotice;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;

function updateDownloaded(string $version): UpdateDownloaded
{
    return new UpdateDownloaded(
        downloadedFile: "/tmp/Sudo Hire-{$version}-arm64-mac.zip",
        version: $version,
        files: [],
        releaseDate: '2026-11-03T12:00:00.000Z',
    );
}

/**
 * @return Collection<int, array{0: Request, 1: mixed}>
 */
function updateNotices()
{
    return Http::recorded(fn (Request $request) => str_ends_with($request->url(), '/notification'));
}

function acceptNotices(): void
{
    Http::fake(['*/notification' => Http::response(['reference' => ShowUpdateNotice::REFERENCE])]);
}

it('listens for a downloaded update', function () {
    Event::fake();

    Event::assertListening(UpdateDownloaded::class, ShowUpdateNotice::class);
});

it('shows a notice that the update installs when the app quits', function () {
    acceptNotices();
    event(updateDownloaded('1.1.0'));

    expect(updateNotices())->toHaveCount(1);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/notification')
        && $request['title'] === 'Sudo Hire'
        && $request['body'] === 'Sudo Hire 1.1.0 is ready. It installs when you quit Sudo Hire.'
        && $request['reference'] === ShowUpdateNotice::REFERENCE);
});

it('shows the notice once for each version', function () {
    acceptNotices();
    event(updateDownloaded('1.1.0'));
    event(updateDownloaded('1.1.0'));
    event(updateDownloaded('1.2.0'));

    expect(updateNotices())->toHaveCount(2);
});

it('does not install the update', function () {
    acceptNotices();
    event(updateDownloaded('1.1.0'));

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'auto-updater/'));
});

it('shows the notice again when the first one failed', function () {
    Http::fake(['*/notification' => Http::sequence()
        ->push(['error' => 'Unauthorized'], 403)
        ->push(['reference' => ShowUpdateNotice::REFERENCE])]);

    event(updateDownloaded('1.1.0'));
    event(updateDownloaded('1.1.0'));

    expect(updateNotices())->toHaveCount(2);
});
