<?php

use App\Filters\TimezoneFilter;
use Carbon\CarbonImmutable;
use Tests\Unit\Filters\Make;

const WINTER = '2026-01-15T12:00:00Z';
const SUMMER = '2026-07-15T12:00:00Z';

it('checks that the job timezones overlap the user offset and the hours', function (?float $min, ?float $max, string $timezone, ?int $hours, string $now, bool $passes) {
    $job = Make::job(['timezoneMin' => $min, 'timezoneMax' => $max]);
    $settings = Make::settings(['timezone' => $timezone, 'timezoneOverlapHours' => $hours]);

    expect(TimezoneFilter::passes($job, $settings, CarbonImmutable::parse($now)))->toBe($passes);
})->with([
    'off' => [-8.0, -5.0, 'Europe/Rome', null, WINTER, true],
    'overlap, CET job' => [1.0, 2.0, 'Europe/Rome', 0, WINTER, true],
    'overlap at the edge' => [-2.0, -2.0, 'Europe/Rome', 3, WINTER, true],
    'no overlap, US job' => [-8.0, -5.0, 'Europe/Rome', 3, WINTER, false],
    'no job timezone' => [null, null, 'Europe/Rome', 0, WINTER, true],
    'winter, Rome is UTC+1: UTC-2 is in reach' => [-2.0, -2.0, 'Europe/Rome', 3, WINTER, true],
    'summer, Rome is UTC+2: UTC-2 is out of reach' => [-2.0, -2.0, 'Europe/Rome', 3, SUMMER, false],
    'summer, Rome is UTC+2: UTC+5 is in reach' => [5.0, 5.0, 'Europe/Rome', 3, SUMMER, true],
    'winter, Rome is UTC+1: UTC+5 is out of reach' => [5.0, 5.0, 'Europe/Rome', 3, WINTER, false],
    'half hour offsets' => [5.5, 5.75, 'Europe/Rome', 3, SUMMER, false],
    'wrap: UTC+14 is UTC-10 in Honolulu' => [13.0, 14.0, 'Pacific/Honolulu', 0, WINTER, true],
    'wrap: user range past UTC+12 reaches UTC-11' => [-11.0, -11.0, 'Pacific/Auckland', 1, SUMMER, true],
    'wrap: no overlap on the other side' => [12.0, 14.0, 'Europe/Rome', 3, WINTER, false],
]);

it('passes the Himalayas "United States" list, -10 to 14, for every user (14 is -10)', function () {
    $raw = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/himalayas/jobs.json'), true);
    $offsets = collect($raw['jobs'])->firstWhere('locationRestrictions', ['United States'])['timezoneRestrictions'];

    expect($offsets)->toBe([-10, -9, -8, -7, -6, -5, 14]);

    $job = Make::job(['timezoneMin' => (float) min($offsets), 'timezoneMax' => (float) max($offsets)]);

    expect(TimezoneFilter::passes($job, Make::settings(['timezoneOverlapHours' => 0]), CarbonImmutable::parse(WINTER)))->toBeTrue();
});

it('reads a job with only one timezone value as one offset', function () {
    $settings = Make::settings(['timezoneOverlapHours' => 1]);
    $now = CarbonImmutable::parse(WINTER);

    expect(TimezoneFilter::passes(Make::job(['timezoneMin' => 2.0]), $settings, $now))->toBeTrue()
        ->and(TimezoneFilter::passes(Make::job(['timezoneMax' => 5.0]), $settings, $now))->toBeFalse();
});
