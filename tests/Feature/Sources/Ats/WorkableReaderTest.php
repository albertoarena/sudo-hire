<?php

use App\Sources\Ats\AtsItem;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\Readers\WorkableReader;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

beforeEach(function () {
    $this->company = CompanyEntry::parse('workable:laravel');
    $this->fetchedAt = CarbonImmutable::parse('2026-11-23T10:00:00Z');
});

function workableItem(int $index = 1, array $changes = []): array
{
    return array_merge(JobFields::json('workable/jobs.json')['jobs'][$index], $changes);
}

it('reads the jobs with their details from the public jobs API', function () {
    Http::fake(['www.workable.com/*' => Http::response(JobFields::fixture('workable/jobs.json'))]);

    $items = (new WorkableReader)->read($this->company);

    expect($items)->toHaveCount(3)
        ->and($items[0]['shortcode'])->toBe('1F39D2C95B');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://www.workable.com/api/accounts/laravel?details=true');
});

it('normalises a remote job field by field', function () {
    $job = (new WorkableReader)->normalise(new AtsItem($this->company, workableItem(), $this->fetchedAt));

    expect(JobFields::of($job))->toBe([
        'source' => 'ats',
        'sourceJobId' => 'workable:laravel:6C1E9F512C',
        'sourceUrl' => 'https://apply.workable.com/j/6C1E9F512C',
        'applyUrl' => 'https://apply.workable.com/j/6C1E9F512C/apply',
        'title' => 'Senior Site Reliability Engineer',
        'company' => 'Laravel',
        'tags' => ['engineering'],
        'workMode' => 'remote',
        'eligibility' => ['type' => 'unknown', 'regions' => [], 'countries' => []],
        'officeLocation' => null,
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => null,
        'contractType' => 'full_time',
        'seniority' => 'senior',
        'postedAt' => '2026-09-30T00:00:00Z',
        'fetchedAt' => '2026-11-23T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via Laravel careers',
    ]);
});

it('reads the countries, the contract type and the title of each job', function (int $index, array $fields) {
    $job = (new WorkableReader)->normalise(new AtsItem($this->company, workableItem($index), $this->fetchedAt));

    expect(JobFields::parsed($job))->toBe(JobFields::expected($fields));
})->with([
    'no employment type and no level' => [0, [
        'workMode' => 'remote',
    ]],
    'full-time, senior' => [1, [
        'workMode' => 'remote',
        'contractType' => 'full_time',
        'seniority' => 'senior',
    ]],
    'several countries and a region in the title' => [2, [
        'workMode' => 'remote',
        'eligibility' => JobFields::restricted([], ['apac']),
        'contractType' => 'full_time',
    ]],
]);

it('makes the description plain text', function () {
    $job = (new WorkableReader)->normalise(new AtsItem($this->company, workableItem(), $this->fetchedAt));

    expect($job->description)
        ->toStartWith('At Laravel, we don’t just build tools')
        ->toEndWith('Company equity')
        ->not->toContain('<p>')
        ->not->toContain('<li>');
});

it('reads the contract type from the employment type, then from the title', function (string $employmentType, string $title, string $expected) {
    $job = (new WorkableReader)->normalise(new AtsItem(
        $this->company,
        workableItem(1, ['employment_type' => $employmentType, 'title' => $title]),
        $this->fetchedAt,
    ));

    expect($job->contractType->value)->toBe($expected);
})->with([
    'full-time' => ['Full-time', 'Engineer', 'full_time'],
    'part-time' => ['Part-time', 'Engineer', 'part_time'],
    'contract' => ['Contract', 'Engineer', 'contract'],
    'empty, as in the fixture' => ['', 'Engineer', 'unknown'],
    'empty, with the type in the title' => ['', 'Engineer (Contract)', 'contract'],
]);

it('gives the office and no eligibility for a job that is not remote', function () {
    $job = (new WorkableReader)->normalise(new AtsItem(
        $this->company,
        workableItem(1, ['telecommuting' => false, 'city' => 'Lisbon', 'state' => '', 'country' => 'Portugal']),
        $this->fetchedAt,
    ));

    expect(JobFields::parsed($job))->toBe(JobFields::expected([
        'officeLocation' => 'Lisbon, Portugal',
        'contractType' => 'full_time',
        'seniority' => 'senior',
    ]));
});

it('gives a remote job with one country only that country', function () {
    $job = (new WorkableReader)->normalise(new AtsItem(
        $this->company,
        workableItem(1, ['locations' => [['countryCode' => 'PL']]]),
        $this->fetchedAt,
    ));

    expect(JobFields::of($job)['eligibility'])->toBe(JobFields::restricted(['PL']));
});

it('gives unknown to a remote job with several countries, because the list can be narrower than the job text', function () {
    $job = (new WorkableReader)->normalise(new AtsItem(
        $this->company,
        workableItem(1, ['title' => 'Product Engineer (Laravel - Remote)', 'locations' => [['countryCode' => 'US'], ['countryCode' => 'DE'], ['countryCode' => 'NL'], ['countryCode' => 'GB']]]),
        $this->fetchedAt,
    ));

    expect(JobFields::of($job)['eligibility'])->toBe(['type' => 'unknown', 'regions' => [], 'countries' => []]);
});

it('counts a country listed twice once', function () {
    $job = (new WorkableReader)->normalise(new AtsItem(
        $this->company,
        workableItem(1, ['locations' => [['countryCode' => 'US'], ['countryCode' => 'us']]]),
        $this->fetchedAt,
    ));

    expect(JobFields::of($job)['eligibility'])->toBe(JobFields::restricted(['US']));
});

it('uses the country name when a remote job has no location list', function () {
    $job = (new WorkableReader)->normalise(new AtsItem(
        $this->company,
        workableItem(1, ['locations' => [], 'country' => 'Argentina']),
        $this->fetchedAt,
    ));

    expect(JobFields::of($job)['eligibility'])->toBe(JobFields::restricted(['AR']));
});

it('uses the job link when the job has no application link', function () {
    $job = (new WorkableReader)->normalise(new AtsItem($this->company, workableItem(1, ['application_url' => null]), $this->fetchedAt));

    expect($job->applyUrl)->toBe('https://apply.workable.com/j/6C1E9F512C');
});

it('skips a job that cannot be normalised', function (array $changes) {
    (new WorkableReader)->normalise(new AtsItem($this->company, workableItem(1, $changes), $this->fetchedAt));
})->throws(SkipItem::class)->with([
    'no title' => [['title' => '']],
    'no link' => [['url' => null]],
    'no id' => [['shortcode' => null]],
    'bad date' => [['published_on' => 'not a date']],
    'no date' => [['published_on' => null]],
]);
