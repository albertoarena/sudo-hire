<?php

use App\Sources\Ats\AtsItem;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\Readers\GreenhouseReader;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

beforeEach(function () {
    $this->company = CompanyEntry::parse('greenhouse:gitlab');
    $this->fetchedAt = CarbonImmutable::parse('2026-10-29T10:00:00Z');
});

function greenhouseItem(array $changes = []): array
{
    return array_merge(JobFields::json('greenhouse/jobs.json')['jobs'][1], $changes);
}

it('reads the jobs with their content from the board API', function () {
    Http::fake(['boards-api.greenhouse.io/*' => Http::response(JobFields::fixture('greenhouse/jobs.json'))]);

    $items = (new GreenhouseReader)->read($this->company);

    expect($items)->toHaveCount(3)
        ->and($items[0]['id'])->toBe(8771512002);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://boards-api.greenhouse.io/v1/boards/gitlab/jobs?content=true');
});

it('normalises a job field by field', function () {
    $job = (new GreenhouseReader)->normalise(new AtsItem($this->company, greenhouseItem(), $this->fetchedAt));

    expect(JobFields::of($job))->toBe([
        'source' => 'ats',
        'sourceJobId' => 'greenhouse:gitlab:8749950002',
        'sourceUrl' => 'https://job-boards.greenhouse.io/gitlab/jobs/8749950002',
        'applyUrl' => 'https://job-boards.greenhouse.io/gitlab/jobs/8749950002',
        'title' => 'Backend Engineer (Ruby), Plan: Spec-Driven Development',
        'company' => 'GitLab',
        'tags' => ['devops engineering'],
        'workMode' => 'remote',
        'eligibility' => ['type' => 'restricted', 'regions' => [], 'countries' => ['PL']],
        'officeLocation' => null,
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => null,
        'contractType' => 'unknown',
        'seniority' => 'unknown',
        'postedAt' => '2026-08-26T11:08:19Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via GitLab careers',
    ]);
});

it('reads the location, the pay range and the title of each job', function (int $index, array $fields) {
    $item = JobFields::json('greenhouse/jobs.json')['jobs'][$index];

    $job = (new GreenhouseReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt));

    expect(JobFields::parsed($job))->toBe(JobFields::expected($fields));
})->with([
    'remote in Canada and the US, with a pay range' => [0, [
        'workMode' => 'remote',
        'eligibility' => JobFields::restricted(['CA', 'US']),
        'salary' => ['min' => 115200.0, 'max' => 162000.0, 'currency' => 'USD', 'period' => 'unknown', 'raw' => "\$115,200\u{2014}\$162,000 USD"],
    ]],
    'remote in Poland' => [1, [
        'workMode' => 'remote',
        'eligibility' => JobFields::restricted(['PL']),
    ]],
    'a city and a country, manager' => [2, [
        'eligibility' => JobFields::restricted(['IN']),
        'seniority' => 'lead',
    ]],
]);

it('reads the contract type from the title', function () {
    $job = (new GreenhouseReader)->normalise(new AtsItem($this->company, greenhouseItem(['title' => 'Recruiting Coordinator (Contract)']), $this->fetchedAt));

    expect($job->contractType->value)->toBe('contract');
});

it('decodes the entities of the content and then makes it plain text', function () {
    $job = (new GreenhouseReader)->normalise(new AtsItem($this->company, greenhouseItem(), $this->fetchedAt));

    expect($job->description)
        ->toStartWith('GitLab is the intelligent orchestration platform for DevSecOps.')
        ->toEndWith('to solve complex problems.')
        ->not->toContain('&lt;')
        ->not->toContain('&nbsp;')
        ->not->toContain('<p>')
        ->not->toContain('<div');
});

it('gives the application deadline as the expiry date', function () {
    $job = (new GreenhouseReader)->normalise(new AtsItem(
        $this->company,
        greenhouseItem(['application_deadline' => '2026-11-30T23:59:00-05:00']),
        $this->fetchedAt,
    ));

    expect($job->expiresAt?->toIso8601ZuluString())->toBe('2026-12-01T04:59:00Z');
});

it('uses the display name when the job has no company name', function () {
    $company = CompanyEntry::parse('greenhouse:gitlab', 'GitLab Inc.');

    $job = (new GreenhouseReader)->normalise(new AtsItem($company, greenhouseItem(['company_name' => null]), $this->fetchedAt));

    expect($job->company)->toBe('GitLab Inc.')
        ->and($job->attribution)->toBe('via GitLab Inc. careers');
});

it('skips a job that cannot be normalised', function (array $changes) {
    (new GreenhouseReader)->normalise(new AtsItem($this->company, greenhouseItem($changes), $this->fetchedAt));
})->throws(SkipItem::class)->with([
    'no title' => [['title' => '']],
    'no link' => [['absolute_url' => null]],
    'no id' => [['id' => null]],
    'bad date' => [['first_published' => 'not a date']],
    'no date' => [['first_published' => null]],
    'deadline before the posted date' => [['application_deadline' => '2020-01-01T00:00:00Z']],
]);

it('narrows an unknown location to the region in the title', function () {
    $item = greenhouseItem(['title' => 'Backend Engineer (Remote, EMEA)', 'location' => ['name' => 'Remote']]);

    expect(JobFields::of((new GreenhouseReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt)))['eligibility'])->toBe(JobFields::restricted([], ['emea']));
});
