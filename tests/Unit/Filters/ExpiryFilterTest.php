<?php

use App\Filters\ExpiryFilter;
use Carbon\CarbonImmutable;
use Tests\Unit\Filters\Make;

it('fails a job that expired before now', function (?string $expiresAt, bool $passes) {
    $job = Make::job(['expiresAt' => $expiresAt === null ? null : CarbonImmutable::parse($expiresAt)]);
    $now = CarbonImmutable::parse('2026-10-30T12:00:00Z');

    expect(ExpiryFilter::passes($job, $now))->toBe($passes);
})->with([
    'no expiry date' => [null, true],
    'expires later' => ['2026-11-30T00:00:00Z', true],
    'expires now' => ['2026-10-30T12:00:00Z', true],
    'expired' => ['2026-10-29T00:00:00Z', false],
]);
