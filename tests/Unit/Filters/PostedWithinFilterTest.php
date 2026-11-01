<?php

use App\Filters\PostedWithinFilter;
use Carbon\CarbonImmutable;
use Tests\Unit\Filters\Make;

it('passes a job posted within the hours of the setting', function (string $postedAt, int $hours, bool $passes) {
    $job = Make::job(['postedAt' => CarbonImmutable::parse($postedAt), 'fetchedAt' => CarbonImmutable::parse('2026-10-30T12:00:00Z')]);
    $now = CarbonImmutable::parse('2026-10-30T12:00:00Z');

    expect(PostedWithinFilter::passes($job, Make::settings(['postedWithinHours' => $hours]), $now))->toBe($passes);
})->with([
    '1 hour ago, 24 hours' => ['2026-10-30T11:00:00Z', 24, true],
    'exactly 24 hours ago, 24 hours' => ['2026-10-29T12:00:00Z', 24, true],
    '25 hours ago, 24 hours' => ['2026-10-29T11:00:00Z', 24, false],
    '2 days ago, 3 days' => ['2026-10-28T12:00:00Z', 72, true],
    '4 days ago, 3 days' => ['2026-10-26T12:00:00Z', 72, false],
    '6 days ago, 7 days' => ['2026-10-24T12:00:00Z', 168, true],
    '8 days ago, 7 days' => ['2026-10-22T12:00:00Z', 168, false],
    '13 days ago, 14 days' => ['2026-10-17T12:00:00Z', 336, true],
    '15 days ago, 14 days' => ['2026-10-15T12:00:00Z', 336, false],
    '29 days ago, 30 days' => ['2026-10-01T12:00:00Z', 720, true],
    '31 days ago, 30 days' => ['2026-09-29T12:00:00Z', 720, false],
]);
