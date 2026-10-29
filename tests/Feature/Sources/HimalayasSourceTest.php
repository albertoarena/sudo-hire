<?php

use App\Sources\Himalayas\HimalayasSource;
use App\Sources\JobSource;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-29T10:00:00Z');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function himalayasItem(int $index = 0, array $changes = [], string $fixture = 'himalayas/search.json'): array
{
    return array_merge(JobFields::json($fixture)['jobs'][$index], $changes);
}

/**
 * A search page in the shape of the fixture, with $count jobs posted at $postedAt.
 */
function himalayasPage(string $name, int $count, string $postedAt = '2026-10-28T12:00:00Z'): array
{
    $page = JobFields::json('himalayas/search.json');
    $page['jobs'] = array_map(fn (int $i) => himalayasItem(0, [
        'guid' => "https://himalayas.app/companies/acme/jobs/{$name}-{$i}",
        'pubDate' => CarbonImmutable::parse($postedAt)->getTimestamp(),
    ]), $count === 0 ? [] : range(1, $count));

    return $page;
}

/**
 * @return list<string> the search URLs sent, in order
 */
function himalayasRequests(): array
{
    return Http::recorded()->map(fn (array $pair) => $pair[0]->url())->values()->all();
}

it('is a job source with its key, attribution and interval', function () {
    $source = new HimalayasSource;

    expect($source)->toBeInstanceOf(JobSource::class)
        ->and($source->key())->toBe('himalayas')
        ->and($source->attribution())->toBe('via Himalayas')
        ->and($source->minInterval()->totalHours)->toEqual(2);
});

it('searches for php and laravel and reads each job once', function () {
    Http::fake(['himalayas.app/*' => Http::response(JobFields::fixture('himalayas/search.json'))]);

    $items = iterator_to_array((new HimalayasSource)->fetch(), false);

    expect($items)->toHaveCount(3)
        ->and(himalayasRequests())->toBe([
            'https://himalayas.app/jobs/api/search?q=php&page=1',
            'https://himalayas.app/jobs/api/search?q=laravel&page=1',
        ]);
});

it('reads the next page until a page is not full', function () {
    Http::fake([
        'himalayas.app/jobs/api/search?q=php&page=1' => Http::response(himalayasPage('php-one', 20)),
        'himalayas.app/jobs/api/search?q=php&page=2' => Http::response(himalayasPage('php-two', 3)),
        'himalayas.app/jobs/api/search?q=laravel&page=1' => Http::response(himalayasPage('laravel-one', 0)),
    ]);

    $items = iterator_to_array((new HimalayasSource)->fetch(), false);

    expect($items)->toHaveCount(23)
        ->and($items[22]['guid'])->toBe('https://himalayas.app/companies/acme/jobs/php-two-3')
        ->and(himalayasRequests())->toBe([
            'https://himalayas.app/jobs/api/search?q=php&page=1',
            'https://himalayas.app/jobs/api/search?q=php&page=2',
            'https://himalayas.app/jobs/api/search?q=laravel&page=1',
        ]);
});

it('stops at the first page with no job newer than $since', function () {
    Http::fake([
        'himalayas.app/jobs/api/search?q=php&page=1' => Http::response(himalayasPage('new', 20, '2026-10-28T12:00:00Z')),
        'himalayas.app/jobs/api/search?q=php&page=2' => Http::response(himalayasPage('old', 20, '2026-10-20T12:00:00Z')),
        'himalayas.app/jobs/api/search?q=laravel&page=1' => Http::response(himalayasPage('laravel-old', 20, '2026-10-20T12:00:00Z')),
    ]);

    $items = iterator_to_array((new HimalayasSource)->fetch(CarbonImmutable::parse('2026-10-27T00:00:00Z')), false);

    expect($items)->toHaveCount(60)
        ->and(himalayasRequests())->toBe([
            'https://himalayas.app/jobs/api/search?q=php&page=1',
            'https://himalayas.app/jobs/api/search?q=php&page=2',
            'https://himalayas.app/jobs/api/search?q=laravel&page=1',
        ]);
});

it('reads the next laravel page when every job on a page was found by the php search', function () {
    Http::fake([
        'himalayas.app/jobs/api/search?q=php&page=1' => Http::response(himalayasPage('shared', 20)),
        'himalayas.app/jobs/api/search?q=php&page=2' => Http::response(himalayasPage('php-two', 3)),
        'himalayas.app/jobs/api/search?q=laravel&page=1' => Http::response(himalayasPage('shared', 20)),
        'himalayas.app/jobs/api/search?q=laravel&page=2' => Http::response(himalayasPage('laravel-two', 3)),
    ]);

    $items = iterator_to_array((new HimalayasSource)->fetch(CarbonImmutable::parse('2026-10-27T00:00:00Z')), false);

    expect($items)->toHaveCount(26)
        ->and($items[25]['guid'])->toBe('https://himalayas.app/companies/acme/jobs/laravel-two-3')
        ->and(himalayasRequests())->toBe([
            'https://himalayas.app/jobs/api/search?q=php&page=1',
            'https://himalayas.app/jobs/api/search?q=php&page=2',
            'https://himalayas.app/jobs/api/search?q=laravel&page=1',
            'https://himalayas.app/jobs/api/search?q=laravel&page=2',
        ]);
});

it('reads at most 5 pages for each search', function () {
    Http::fake(fn (Request $request) => Http::response(himalayasPage(md5($request->url()), 20)));

    $items = iterator_to_array((new HimalayasSource)->fetch(), false);

    expect($items)->toHaveCount(200)
        ->and(himalayasRequests())->toHaveCount(10)
        ->and(himalayasRequests()[4])->toBe('https://himalayas.app/jobs/api/search?q=php&page=5');
});

it('normalises a job field by field', function () {
    $job = (new HimalayasSource)->normalise(himalayasItem(0));

    expect(JobFields::of($job))->toBe([
        'source' => 'himalayas',
        'sourceJobId' => 'https://himalayas.app/companies/reiz-tech-uab/jobs/sr-php-developer-laravel-or-symfony-2365293251',
        'sourceUrl' => 'https://himalayas.app/companies/reiz-tech-uab/jobs/sr-php-developer-laravel-or-symfony-2365293251',
        'applyUrl' => 'https://himalayas.app/companies/reiz-tech-uab/jobs/sr-php-developer-laravel-or-symfony-2365293251?utm_source=api',
        'title' => 'SR. PHP DEVELOPER (LARAVEL OR SYMFONY)',
        'company' => 'REIZ TECH UAB',
        'tags' => [
            'php developer', 'laravel developer', 'backend engineer', 'software developer', 'senior php developer',
            'senior php engineer', 'senior laravel developer', 'senior full stack php developer',
            'senior php development', 'php symfony developer', 'developer',
        ],
        'workMode' => 'remote',
        'eligibility' => ['type' => 'worldwide', 'regions' => [], 'countries' => []],
        'officeLocation' => null,
        'timezoneMin' => -11.0,
        'timezoneMax' => 14.0,
        'salary' => ['min' => 5500.0, 'max' => 7000.0, 'currency' => 'EUR', 'period' => 'month', 'raw' => '5500 - 7000 EUR monthly'],
        'contractType' => 'full_time',
        'seniority' => 'senior',
        'postedAt' => '2026-10-02T18:19:32Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => '2026-12-01T17:19:32Z',
        'attribution' => 'via Himalayas',
    ]);

    expect($job->description)
        ->toStartWith("ABOUT REIZ TECH\n\nReiz Tech is more than just an IT company.")
        ->not->toContain('<h3>');
});

it('keeps fractional timezone offsets exact', function () {
    $job = (new HimalayasSource)->normalise(himalayasItem(0, ['timezoneRestrictions' => [5.75, -9.5, 3.5]]));

    expect($job->timezoneMin)->toBe(-9.5)
        ->and($job->timezoneMax)->toBe(5.75);
});

it('gives no timezone for an empty timezone list', function () {
    $job = (new HimalayasSource)->normalise(himalayasItem(0, ['timezoneRestrictions' => []]));

    expect($job->timezoneMin)->toBeNull()
        ->and($job->timezoneMax)->toBeNull();
});

it('gives worldwide for an empty location list', function () {
    expect((new HimalayasSource)->normalise(himalayasItem(0, ['locationRestrictions' => []]))->eligibility->type->value)->toBe('worldwide');
});

it('gives unknown eligibility for country names until the country table exists', function () {
    expect((new HimalayasSource)->normalise(himalayasItem(1))->eligibility->type->value)->toBe('unknown');
});

it('gives no salary when the period and currency have no amounts', function () {
    $job = (new HimalayasSource)->normalise(himalayasItem(1, ['currency' => 'USD', 'salaryPeriod' => 'annual']));

    expect($job->salary)->toBeNull();
});

it('maps the salary period', function (string $period, string $expected) {
    expect((new HimalayasSource)->normalise(himalayasItem(0, ['salaryPeriod' => $period]))->salary?->period->value)->toBe($expected);
})->with([
    ['annual', 'year'],
    ['monthly', 'month'],
    ['weekly', 'unknown'],
]);

it('gives no salary for a currency that is not ISO 4217', function () {
    expect((new HimalayasSource)->normalise(himalayasItem(0, ['currency' => null]))->salary)->toBeNull();
});

it('takes the lowest seniority in the list', function (array $levels, string $seniority) {
    expect((new HimalayasSource)->normalise(himalayasItem(0, ['seniority' => $levels]))->seniority->value)->toBe($seniority);
})->with([
    [['Mid-level', 'Senior'], 'mid'],
    [['Senior', 'Entry-level'], 'junior'],
    [['Junior'], 'junior'],
    [['Director', 'Senior'], 'senior'],
    [['Manager'], 'lead'],
    [['Executive'], 'lead'],
    [['Intern'], 'unknown'],
    [[], 'unknown'],
]);

it('reads the lowest seniority of a real job', function () {
    $job = (new HimalayasSource)->normalise(himalayasItem(0, fixture: 'himalayas/search-mid.json'));

    expect($job->seniority->value)->toBe('mid');
});

it('maps the employment type to the contract type', function (string $type, string $contract) {
    expect((new HimalayasSource)->normalise(himalayasItem(0, ['employmentType' => $type]))->contractType->value)->toBe($contract);
})->with([
    ['Full Time', 'full_time'],
    ['Part Time', 'part_time'],
    ['Contractor', 'contract'],
    ['Intern', 'unknown'],
]);

it('reads a job from the browse feed', function () {
    $job = (new HimalayasSource)->normalise(himalayasItem(0, fixture: 'himalayas/jobs.json'));

    expect($job->postedAt->toIso8601ZuluString())->toBe('2026-10-28T12:38:25Z')
        ->and($job->expiresAt?->toIso8601ZuluString())->toBe('2026-12-27T12:38:24Z')
        ->and($job->applyUrl)->toEndWith('?utm_source=api');
});

it('uses the Himalayas job page when there is no apply link', function () {
    $job = (new HimalayasSource)->normalise(himalayasItem(0, ['applicationLink' => null]));

    expect($job->applyUrl)->toBe($job->sourceUrl);
});

it('gives no expiry date when the job has none', function () {
    expect((new HimalayasSource)->normalise(himalayasItem(0, ['expiryDate' => null]))->expiresAt)->toBeNull();
});

it('fails when the API does not answer', function () {
    Http::fake(['himalayas.app/*' => Http::response('', 503)]);

    iterator_to_array((new HimalayasSource)->fetch());
})->throws(RequestException::class);

it('fails when the response has no job list', function () {
    Http::fake(['himalayas.app/*' => Http::response('{"totalCount":0}')]);

    iterator_to_array((new HimalayasSource)->fetch());
})->throws(RuntimeException::class, 'The Himalayas response has no job list.');

it('skips a job that cannot be normalised', function (mixed $item) {
    (new HimalayasSource)->normalise($item);
})->throws(SkipItem::class)->with([
    'not an array' => ['text'],
    'no link' => [fn () => himalayasItem(0, ['guid' => null])],
    'no title' => [fn () => himalayasItem(0, ['title' => ''])],
    'no company' => [fn () => himalayasItem(0, ['companyName' => null])],
    'no date' => [fn () => himalayasItem(0, ['pubDate' => null])],
    'a date that is not a number' => [fn () => himalayasItem(0, ['pubDate' => 'yesterday'])],
    'an expiry date before the posted date' => [fn () => himalayasItem(0, ['expiryDate' => 1690965172])],
    'a timezone out of range' => [fn () => himalayasItem(0, ['timezoneRestrictions' => [-13]])],
]);
