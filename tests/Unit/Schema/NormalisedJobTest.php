<?php

use App\Schema\Eligibility;
use App\Schema\InvalidJob;
use App\Schema\NormalisedJob;
use App\Schema\Salary;

it('keeps apply_url and source_url unchanged', function () {
    $applyUrl = 'https://jobs.lever.co/spotify/0d7d1f6c-2a1e-4b8a-9b1e-6c6c3b1f2a10/apply?lever-source=Sudo%20Hire&x=1#form';
    $sourceUrl = 'https://himalayas.app/companies/nvidia/jobs/senior-engineer?utm_source=api';

    $job = new NormalisedJob(...jobAttributes([
        'applyUrl' => $applyUrl,
        'sourceUrl' => $sourceUrl,
    ]));

    expect($job->applyUrl)->toBe($applyUrl)
        ->and($job->sourceUrl)->toBe($sourceUrl);
});

it('rejects a job without a required field', function (string $field) {
    new NormalisedJob(...jobAttributes([$field => '']));
})->throws(InvalidJob::class)->with([
    'source',
    'sourceJobId',
    'sourceUrl',
    'title',
    'company',
    'applyUrl',
]);

it('rejects a required field with only spaces', function () {
    new NormalisedJob(...jobAttributes(['title' => '   ']));
})->throws(InvalidJob::class);

it('rejects a link that is not http or https', function (string $field) {
    new NormalisedJob(...jobAttributes([$field => 'javascript:alert(1)']));
})->throws(InvalidJob::class)->with(['applyUrl', 'sourceUrl']);

it('stores tags in lowercase, trimmed and without duplicates', function () {
    $job = new NormalisedJob(...jobAttributes(['tags' => ['PHP', ' Laravel ', 'php', '']]));

    expect($job->tags)->toBe(['php', 'laravel']);
});

it('accepts fractional timezone offsets', function () {
    $job = new NormalisedJob(...jobAttributes(['timezoneMin' => -9.5, 'timezoneMax' => 5.75]));

    expect($job->timezoneMin)->toBe(-9.5)
        ->and($job->timezoneMax)->toBe(5.75);
});

it('rejects a timezone outside UTC-12 to UTC+14', function (float $offset) {
    new NormalisedJob(...jobAttributes(['timezoneMin' => $offset, 'timezoneMax' => 14.0]));
})->throws(InvalidJob::class)->with([-12.5, 14.5]);

it('rejects a timezone range where min is above max', function () {
    new NormalisedJob(...jobAttributes(['timezoneMin' => 3.0, 'timezoneMax' => -1.0]));
})->throws(InvalidJob::class);

it('stores an optional expiry date', function () {
    $expiresAt = Carbon\CarbonImmutable::parse('2026-12-01T00:00:00Z');

    expect((new NormalisedJob(...jobAttributes()))->expiresAt)->toBeNull()
        ->and((new NormalisedJob(...jobAttributes(['expiresAt' => $expiresAt])))->expiresAt)->toBe($expiresAt);
});

it('rejects an expiry date before the posted date', function () {
    new NormalisedJob(...jobAttributes(['expiresAt' => Carbon\CarbonImmutable::parse('2026-10-01T00:00:00Z')]));
})->throws(InvalidJob::class);

it('stores eligibility and salary objects', function () {
    $eligibility = Eligibility::restricted(regions: ['eu'], countries: ['IT']);
    $salary = Salary::fromArray(['min' => 60000, 'max' => 70000, 'currency' => 'EUR', 'period' => 'year', 'raw' => '60-70k EUR']);

    $job = new NormalisedJob(...jobAttributes(['eligibility' => $eligibility, 'salary' => $salary]));

    expect($job->eligibility)->toBe($eligibility)
        ->and($job->salary)->toBe($salary);
});

it('builds the same fingerprint for the same company and title from two sources', function () {
    $a = new NormalisedJob(...jobAttributes(['source' => 'larajobs', 'company' => 'Acme GmbH', 'title' => 'Senior Laravel Developer']));
    $b = new NormalisedJob(...jobAttributes(['source' => 'jobicy', 'company' => ' acme gmbh', 'title' => 'Senior  Laravel developer ']));
    $c = new NormalisedJob(...jobAttributes(['company' => 'Acme GmbH', 'title' => 'Junior Laravel Developer']));

    expect($a->fingerprint)->toBe($b->fingerprint)
        ->and($a->fingerprint)->not->toBe($c->fingerprint);
});
