<?php

use App\List\JobState;
use App\Schema\Eligibility;
use App\Settings\SettingsStore;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
    ApiHelpers::settings(['country' => 'IT']);
    Http::fake();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * A stored job, posted $hoursAgo hours ago, with the given eligibility.
 */
function previewJob(string $id, Eligibility $eligibility, int $hoursAgo = 1, array $overrides = []): int
{
    return ApiHelpers::job($id, array_merge([
        'eligibility' => $eligibility,
        'postedAt' => CarbonImmutable::now()->subHours($hoursAgo),
    ], $overrides))->id;
}

function preview(string $country): array
{
    return test()->getJson('/api/stamp-preview?country='.$country)->assertOk()->json('jobs');
}

it('gives one job that the country admits and one that it refuses', function () {
    $germany = previewJob('1', Eligibility::restricted(countries: ['DE']), 1, ['title' => 'PHP Developer', 'company' => 'Acme']);
    $us = previewJob('2', Eligibility::restricted(countries: ['US']), 2, ['title' => 'Laravel Engineer', 'company' => 'Globex']);

    expect(preview('DE'))->toBe([
        ['id' => $germany, 'title' => 'PHP Developer', 'company' => 'Acme', 'stamp' => ['kind' => 'admitted', 'place' => 'DE']],
        ['id' => $us, 'title' => 'Laravel Engineer', 'company' => 'Globex', 'stamp' => ['kind' => 'refused', 'place' => null]],
    ]);

    expect(array_column(preview('US'), 'id'))->toBe([$us, $germany])
        ->and(array_column(array_column(preview('US'), 'stamp'), 'kind'))->toBe(['admitted', 'refused']);
});

it('gives the region on the stamp when the job admits a region', function () {
    previewJob('1', Eligibility::restricted(regions: ['europe']));

    expect(preview('FR')[0]['stamp'])->toBe(['kind' => 'admitted', 'place' => 'Europe']);
});

it('does not change the stored settings', function () {
    previewJob('1', Eligibility::worldwide());

    preview('DE');

    expect(app(SettingsStore::class)->load()->country)->toBe('IT');
});

it('prefers the newest jobs', function () {
    previewJob('1', Eligibility::worldwide(), 5);
    $newest = previewJob('2', Eligibility::worldwide(), 1);
    $refused = previewJob('3', Eligibility::restricted(countries: ['US']), 9);
    previewJob('4', Eligibility::restricted(countries: ['US']), 10);

    expect(array_column(preview('DE'), 'id'))->toBe([$newest, $refused]);
});

it('gives two admitted jobs when the country refuses none', function () {
    $first = previewJob('1', Eligibility::worldwide(), 1);
    $second = previewJob('2', Eligibility::worldwide(), 2);

    expect(array_column(preview('DE'), 'id'))->toBe([$first, $second]);
});

it('uses a job with no stated location only when no other job is left', function () {
    $unknown = previewJob('1', Eligibility::unknown(), 1);
    $admitted = previewJob('2', Eligibility::worldwide(), 2);

    expect(preview('DE'))->toBe([
        ['id' => $admitted, 'title' => 'Laravel Developer 2', 'company' => 'Acme', 'stamp' => ['kind' => 'admitted', 'place' => 'DE']],
        ['id' => $unknown, 'title' => 'Laravel Developer 1', 'company' => 'Acme', 'stamp' => ['kind' => 'not_stated', 'place' => 'Location']],
    ]);
});

it('gives what there is with fewer jobs', function () {
    $only = previewJob('1', Eligibility::worldwide());

    expect(array_column(preview('DE'), 'id'))->toBe([$only]);

    $this->getJson('/api/stamp-preview?country=US')->assertOk();
});

it('gives no jobs when none are stored', function () {
    expect(preview('DE'))->toBe([]);
});

it('leaves out hidden jobs', function () {
    $job = ApiHelpers::job('1', ['eligibility' => Eligibility::worldwide()]);
    app(JobStore::class)->setState($job, JobState::Hidden);

    expect(preview('DE'))->toBe([]);
});

it('gives 422 for a country that is not in the table', function (?string $country) {
    $this->getJson('/api/stamp-preview'.($country === null ? '' : '?country='.$country))
        ->assertUnprocessable()
        ->assertExactJson(['message' => 'Choose a country from the list.']);
})->with(['not a code' => ['Italy'], 'unknown code' => ['ZZ'], 'lower case' => ['de'], 'missing' => [null]]);
