<?php

use App\Providers\NativeAppServiceProvider;
use App\Updates\UpdateChecks;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-03 12:00:00');
    config(['nativephp-internal.running' => true, 'nativephp.updater.enabled' => true]);
    Http::fake();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function updateCheckRequests(): int
{
    return Http::recorded(fn (Request $request) => str_ends_with($request->url(), 'auto-updater/check-for-updates'))->count();
}

it('runs the update check every hour in the schedule', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event) => str_contains((string) $event->command, 'hire:check-updates'))
        ->values();

    expect($events)->toHaveCount(1)
        ->and($events[0]->expression)->toBe('0 * * * *');
});

it('records the check that the app makes when it starts', function () {
    ApiHelpers::settings(['country' => 'IT']);

    (new NativeAppServiceProvider)->boot();

    expect(app(UpdateChecks::class)->lastCheck()?->toDateTimeString())->toBe('2026-11-03 12:00:00');
});

it('checks for updates when no check is recorded', function () {
    $this->artisan('hire:check-updates')->assertExitCode(0);

    expect(updateCheckRequests())->toBe(1)
        ->and(app(UpdateChecks::class)->lastCheck()?->toDateTimeString())->toBe('2026-11-03 12:00:00');
});

it('does not check again within a day', function () {
    app(UpdateChecks::class)->record(CarbonImmutable::parse('2026-11-02 13:00:00'));

    $this->artisan('hire:check-updates')->assertExitCode(0);

    expect(updateCheckRequests())->toBe(0);
});

it('checks again one day after the last check', function () {
    app(UpdateChecks::class)->record(CarbonImmutable::parse('2026-11-02 12:00:00'));

    $this->artisan('hire:check-updates')->assertExitCode(0);

    expect(updateCheckRequests())->toBe(1);
});

it('does not check when the updater is off', function () {
    config(['nativephp.updater.enabled' => false]);

    $this->artisan('hire:check-updates')->assertExitCode(0);

    expect(updateCheckRequests())->toBe(0);
});

it('does not check outside the app', function () {
    config(['nativephp-internal.running' => false]);

    $this->artisan('hire:check-updates')->assertExitCode(0);

    expect(updateCheckRequests())->toBe(0);
});
