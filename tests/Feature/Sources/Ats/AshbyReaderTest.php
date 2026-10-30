<?php

use App\Sources\Ats\AtsItem;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\Readers\AshbyReader;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

beforeEach(function () {
    $this->company = CompanyEntry::parse('ashby:ashby');
    $this->fetchedAt = CarbonImmutable::parse('2026-10-29T10:00:00Z');
});

function ashbyItem(int $index = 0, array $changes = []): array
{
    return array_merge(JobFields::json('ashby/job-board.json')['jobs'][$index], $changes);
}

it('reads the jobs with their compensation from the job board API', function () {
    Http::fake(['api.ashbyhq.com/*' => Http::response(JobFields::fixture('ashby/job-board.json'))]);

    $items = (new AshbyReader)->read($this->company);

    expect($items)->toHaveCount(3)
        ->and($items[0]['id'])->toBe('3b9b0141-86e7-46b2-b6b4-ed039b84cdc4');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.ashbyhq.com/posting-api/job-board/ashby?includeCompensation=true');
});

it('normalises a job field by field', function () {
    $job = (new AshbyReader)->normalise(new AtsItem($this->company, ashbyItem(0), $this->fetchedAt));

    expect(JobFields::of($job))->toBe([
        'source' => 'ats',
        'sourceJobId' => 'ashby:ashby:3b9b0141-86e7-46b2-b6b4-ed039b84cdc4',
        'sourceUrl' => 'https://jobs.ashbyhq.com/ashby/3b9b0141-86e7-46b2-b6b4-ed039b84cdc4',
        'applyUrl' => 'https://jobs.ashbyhq.com/ashby/3b9b0141-86e7-46b2-b6b4-ed039b84cdc4/application',
        'title' => 'Senior Software Engineer, Product Engineering - EU',
        'company' => 'Ashby',
        'tags' => ['engineering', 'emea engineering'],
        'workMode' => 'remote',
        'eligibility' => ['type' => 'restricted', 'regions' => ['eu'], 'countries' => ['ES', 'IT', 'CH', 'HR', 'IE', 'SE', 'RO', 'PT', 'DE', 'EE']],
        'officeLocation' => null,
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => ['min' => 108000.0, 'max' => 145000.0, 'currency' => 'EUR', 'period' => 'year', 'raw' => '€108K - €145K'],
        'contractType' => 'full_time',
        'seniority' => 'senior',
        'postedAt' => '2026-04-30T19:45:44Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via Ashby careers',
    ]);

    expect($job->description)->toStartWith('Hi 👋🏾 I’m Hiring Manager Name');
});

it('takes the company name from the company entry', function () {
    $job = (new AshbyReader)->normalise(new AtsItem(CompanyEntry::parse('ashby:ashby', 'Ashby Inc.'), ashbyItem(0), $this->fetchedAt));

    expect($job->company)->toBe('Ashby Inc.')
        ->and($job->attribution)->toBe('via Ashby Inc. careers');
});

it('uses the salary currency, not the country', function () {
    $job = (new AshbyReader)->normalise(new AtsItem($this->company, ashbyItem(2), $this->fetchedAt));

    expect(JobFields::of($job)['salary'])->toBe([
        'min' => 11950000.0, 'max' => 13750000.0, 'currency' => 'JPY', 'period' => 'year', 'raw' => '¥11,950,000 - ¥13,750,000',
    ]);
});

it('maps the salary interval', function (string $interval, string $period) {
    $item = ashbyItem(1);
    $item['compensation']['summaryComponents'][1]['interval'] = $interval;

    $job = (new AshbyReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt));

    expect($job->salary?->period->value)->toBe($period);
})->with([
    ['1 YEAR', 'year'],
    ['1 MONTH', 'month'],
    ['1 DAY', 'day'],
    ['1 HOUR', 'hour'],
    ['2 WEEK', 'unknown'],
]);

it('gives no salary when there is no salary component', function () {
    $item = ashbyItem(1);
    unset($item['compensation']['summaryComponents'][1]);

    expect((new AshbyReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt))->salary)->toBeNull();
});

it('gives no salary when the job has no compensation', function () {
    $item = ashbyItem(1);
    unset($item['compensation']);

    expect((new AshbyReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt))->salary)->toBeNull();
});

it('reads the countries of the job and its secondary locations', function (int $index, array $eligibility, string $seniority) {
    $job = (new AshbyReader)->normalise(new AtsItem($this->company, ashbyItem($index), $this->fetchedAt));

    expect(JobFields::of($job)['eligibility'])->toBe($eligibility)
        ->and($job->seniority->value)->toBe($seniority);
})->with([
    'European Union and countries' => [0, JobFields::restricted(['ES', 'IT', 'CH', 'HR', 'IE', 'SE', 'RO', 'PT', 'DE', 'EE'], ['eu']), 'senior'],
    'Australia' => [1, JobFields::restricted(['AU']), 'unknown'],
    'Australia and APAC countries' => [2, JobFields::restricted(['AU', 'NZ', 'SG', 'KR', 'JP']), 'unknown'],
]);

it('gives unknown eligibility for a hybrid or on-site job', function (string $type) {
    $job = (new AshbyReader)->normalise(new AtsItem($this->company, ashbyItem(1, ['workplaceType' => $type, 'isRemote' => false]), $this->fetchedAt));

    expect($job->eligibility->type->value)->toBe('unknown');
})->with(['Hybrid', 'OnSite']);

it('takes the contract type from the title when the employment type has no case', function () {
    $item = ashbyItem(1, ['employmentType' => 'Temporary', 'title' => 'Recruiting Coordinator (Contract)']);

    expect((new AshbyReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt))->contractType->value)->toBe('contract');
});

it('maps the workplace type', function (?string $type, bool $isRemote, string $mode, ?string $office) {
    $job = (new AshbyReader)->normalise(new AtsItem(
        $this->company,
        ashbyItem(1, ['workplaceType' => $type, 'isRemote' => $isRemote]),
        $this->fetchedAt,
    ));

    expect($job->workMode->value)->toBe($mode)
        ->and($job->officeLocation)->toBe($office);
})->with([
    ['Remote', true, 'remote', null],
    ['Hybrid', false, 'hybrid', 'Australia'],
    ['OnSite', false, 'onsite', 'Australia'],
    [null, true, 'remote', null],
    [null, false, 'unknown', null],
]);

it('maps the employment type', function (string $type, string $contract) {
    $job = (new AshbyReader)->normalise(new AtsItem($this->company, ashbyItem(1, ['employmentType' => $type]), $this->fetchedAt));

    expect($job->contractType->value)->toBe($contract);
})->with([
    ['FullTime', 'full_time'],
    ['PartTime', 'part_time'],
    ['Contract', 'contract'],
    ['Intern', 'unknown'],
    ['Temporary', 'unknown'],
]);

it('skips a job that cannot be normalised', function (array $changes) {
    (new AshbyReader)->normalise(new AtsItem($this->company, ashbyItem(0, $changes), $this->fetchedAt));
})->throws(SkipItem::class)->with([
    'no title' => [['title' => '']],
    'no link' => [['jobUrl' => null]],
    'no id' => [['id' => null]],
    'no date' => [['publishedAt' => null]],
]);
