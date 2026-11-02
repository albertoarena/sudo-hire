<?php

use App\List\JobList;
use App\List\JobState;
use App\Models\ExchangeRate;
use App\Models\Job;
use App\Models\StoredSettings;
use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Filters\Make;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
    listSettings();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function listSettings(array $overrides = []): void
{
    StoredSettings::query()->delete();
    StoredSettings::query()->create(['data' => Make::settings(array_merge(['country' => 'IT'], $overrides))->toArray()]);
}

/**
 * Stores one job, open worldwide, fetched now and posted the given hours ago.
 */
function listJob(string $id, array $overrides = [], int $hoursAgo = 1): Job
{
    $job = new NormalisedJob(...jobAttributes(array_merge([
        'sourceJobId' => $id,
        'sourceUrl' => "https://example.com/jobs/{$id}",
        'applyUrl' => "https://example.com/jobs/{$id}/apply",
        'title' => "Laravel Developer {$id}",
        'eligibility' => Eligibility::worldwide(),
        'postedAt' => CarbonImmutable::now()->subHours($hoursAgo),
        'fetchedAt' => CarbonImmutable::now(),
    ], $overrides)));

    app(JobStore::class)->save([$job]);

    return Job::query()->where('source', $job->source)->where('source_job_id', $job->sourceJobId)->sole();
}

function jobList(): array
{
    return app(JobList::class)->build()->toArray();
}

/**
 * @return list<int>
 */
function rowIds(array $rows): array
{
    return array_column($rows, 'id');
}

it('lists the new matches, newest first', function () {
    $older = listJob('1', hoursAgo: 5);
    $newer = listJob('2', hoursAgo: 1);
    listJob('3', ['workMode' => WorkMode::Onsite]);

    expect(rowIds(jobList()['new']))->toBe([$newer->id, $older->id]);
});

it('keeps the "Location not stated" group apart, with its count', function () {
    $match = listJob('1');
    $unknown = listJob('2', ['eligibility' => Eligibility::unknown()]);

    $list = jobList();

    expect(rowIds($list['new']))->toBe([$match->id])
        ->and(rowIds($list['locationNotStated']))->toBe([$unknown->id])
        ->and($list['counts']['locationNotStated'])->toBe(1);
});

it('lists saved and applied jobs apart, newest state first, also when they fail the filters now', function () {
    $store = app(JobStore::class);
    $first = $store->setState(listJob('1'), JobState::Saved);
    CarbonImmutable::setTestNow('2026-11-02T11:00:00Z');
    $second = $store->setState(listJob('2'), JobState::Saved);
    $applied = $store->setState(listJob('3'), JobState::Applied);
    $store->setState(listJob('4'), JobState::Hidden);
    listJob('5');

    listSettings(['keywordsInclude' => ['golang']]);
    $list = jobList();

    expect(rowIds($list['saved']))->toBe([$second->id, $first->id])
        ->and(rowIds($list['applied']))->toBe([$applied->id])
        ->and($list['new'])->toBe([])
        ->and($list['locationNotStated'])->toBe([]);
});

it('leaves hidden, saved and applied jobs out of New', function () {
    $store = app(JobStore::class);
    $store->setState(listJob('1'), JobState::Hidden);
    $store->setState(listJob('2'), JobState::Saved);
    $store->setState(listJob('3', ['eligibility' => Eligibility::unknown()]), JobState::Applied);
    $new = listJob('4');

    $list = jobList();

    expect(rowIds($list['new']))->toBe([$new->id])
        ->and($list['locationNotStated'])->toBe([]);
});

it('counts the tabs and the unseen New matches without the group', function () {
    $store = app(JobStore::class);
    listJob('1');
    listJob('2');
    Job::query()->where('source_job_id', '2')->update(['seen_at' => CarbonImmutable::now()]);
    listJob('3', ['eligibility' => Eligibility::unknown()]);
    $store->setState(listJob('4'), JobState::Saved);
    $store->setState(listJob('5'), JobState::Applied);
    $store->setState(listJob('6'), JobState::Applied);

    expect(jobList()['counts'])->toBe(['new' => 2, 'saved' => 1, 'applied' => 2, 'locationNotStated' => 1, 'unseen' => 1])
        ->and(app(JobList::class)->unseenCount())->toBe(1);
});

it('gives every field of a row', function () {
    ExchangeRate::query()->create(['currency' => 'USD', 'rate' => 1.25, 'rate_date' => '2026-11-01', 'fetched_at' => CarbonImmutable::now()]);
    $job = listJob('1', [
        'title' => 'Senior Laravel Developer',
        'company' => 'Acme',
        'eligibility' => Eligibility::restricted(['europe'], ['IT', 'DE']),
        'salary' => new Salary(100000, 125000, 'USD', SalaryPeriod::Year, '$100k - $125k'),
        'contractType' => ContractType::FullTime,
        'seniority' => Seniority::Senior,
        'postedAt' => CarbonImmutable::parse('2026-11-01T06:30:00Z'),
        'attribution' => 'via Fake',
    ]);

    expect(jobList()['new'][0])->toBe([
        'id' => $job->id,
        'title' => 'Senior Laravel Developer',
        'company' => 'Acme',
        'source' => 'fake',
        'attribution' => 'via Fake',
        'alsoOn' => [],
        'postedAt' => '2026-11-01T06:30:00Z',
        'salary' => [
            'original' => '$100k - $125k',
            'converted' => '≈ EUR 80,000-100,000 a year',
            'unknown' => false,
            'ote' => false,
        ],
        'terms' => ['contractType' => 'full_time', 'seniority' => 'senior'],
        'stamp' => ['kind' => 'admitted', 'place' => 'IT'],
        'sourceUrl' => 'https://example.com/jobs/1',
        'applyUrl' => 'https://example.com/jobs/1/apply',
        'seen' => false,
        'state' => null,
    ]);
});

it('gives the "?" and OTE flags of the salary', function () {
    listSettings(['minAnnualSalary' => 50000]);
    listJob('1', ['salary' => null]);
    listJob('2', ['salary' => new Salary(90000, null, 'EUR', SalaryPeriod::Year, '€90k OTE')], hoursAgo: 2);

    $rows = jobList()['new'];

    expect($rows[0]['salary'])->toBe(['original' => null, 'converted' => null, 'unknown' => true, 'ote' => false])
        ->and($rows[1]['salary'])->toBe(['original' => '€90k OTE', 'converted' => null, 'unknown' => false, 'ote' => true]);
});

it('gives the stamp place: country, region or "Location"', function () {
    listJob('1', ['eligibility' => Eligibility::restricted([], ['IT'])], hoursAgo: 1);
    listJob('2', ['eligibility' => Eligibility::restricted(['europe'])], hoursAgo: 2);
    listJob('3', ['eligibility' => Eligibility::worldwide()], hoursAgo: 3);
    listJob('4', ['eligibility' => Eligibility::unknown()], hoursAgo: 4);

    $list = jobList();

    expect(array_column($list['new'], 'stamp'))->toBe([
        ['kind' => 'admitted', 'place' => 'IT'],
        ['kind' => 'admitted', 'place' => 'Europe'],
        ['kind' => 'admitted', 'place' => 'Location'],
    ])->and($list['locationNotStated'][0]['stamp'])->toBe(['kind' => 'not_stated', 'place' => 'Location']);
});

it('gives the links of the first enabled source and the other sources as "also on"', function () {
    $job = listJob('1', ['title' => 'Senior Laravel Developer', 'company' => 'Acme', 'attribution' => 'via Fake']);
    app(JobStore::class)->save([
        new NormalisedJob(...jobAttributes([
            'source' => 'other',
            'sourceJobId' => 'x-7',
            'sourceUrl' => 'https://other.example/jobs/7',
            'applyUrl' => 'https://other.example/jobs/7/apply',
            'eligibility' => Eligibility::worldwide(),
            'postedAt' => CarbonImmutable::now()->subHour(),
            'fetchedAt' => CarbonImmutable::now(),
            'attribution' => 'via Acme careers',
        ])),
    ]);

    $row = jobList()['new'][0];

    expect([$row['source'], $row['attribution'], $row['alsoOn'], $row['sourceUrl'], $row['applyUrl']])
        ->toBe(['fake', 'via Fake', ['Acme careers'], 'https://example.com/jobs/1', 'https://example.com/jobs/1/apply']);

    listSettings(['disabledSources' => ['fake']]);
    $row = jobList()['new'][0];

    expect($row['id'])->toBe($job->id)
        ->and([$row['source'], $row['attribution'], $row['alsoOn'], $row['sourceUrl'], $row['applyUrl']])
        ->toBe(['other', 'via Acme careers', ['Fake'], 'https://other.example/jobs/7', 'https://other.example/jobs/7/apply']);
});

it('gives seen and state on each row', function () {
    $job = app(JobStore::class)->setState(listJob('1'), JobState::Saved);

    $row = jobList()['saved'][0];

    expect($row['id'])->toBe($job->id)
        ->and($row['seen'])->toBeTrue()
        ->and($row['state'])->toBe('saved');
});

it('gives the ids of the unseen New matches that existed at a given time', function () {
    $old = listJob('1');
    listJob('2', ['eligibility' => Eligibility::unknown()]);
    listJob('3');
    Job::query()->where('source_job_id', '3')->update(['seen_at' => CarbonImmutable::now()]);
    CarbonImmutable::setTestNow('2026-11-02T10:05:00Z');
    listJob('4');

    expect(app(JobList::class)->unseenIds(CarbonImmutable::parse('2026-11-02T10:01:00Z')))->toBe([$old->id]);
});
