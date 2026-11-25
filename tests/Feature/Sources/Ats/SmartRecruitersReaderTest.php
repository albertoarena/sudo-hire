<?php

use App\Sources\Ats\AtsItem;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\Readers\SmartRecruitersReader;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

beforeEach(function () {
    $this->company = CompanyEntry::parse('smartrecruiters:aboutyougmbh');
    $this->fetchedAt = CarbonImmutable::parse('2026-11-23T10:00:00Z');
});

function smartRecruitersItem(int $index = 0, array $changes = []): array
{
    return array_merge(JobFields::json('smartrecruiters/postings.json')['content'][$index], $changes);
}

it('reads the postings from the posting API', function () {
    Http::fake(['api.smartrecruiters.com/*' => Http::response(JobFields::fixture('smartrecruiters/postings.json'))]);

    $items = (new SmartRecruitersReader)->read($this->company);

    expect($items)->toHaveCount(3)
        ->and(array_column($items, 'id'))->toBe(['744000155066262', '744000158738024', '744000161508939']);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.smartrecruiters.com/v1/companies/aboutyougmbh/postings?offset=0&limit=100');
});

it('reads the next page when the company has more than 100 postings', function () {
    $page = array_merge(JobFields::json('smartrecruiters/postings.json'), ['totalFound' => 150]);

    Http::fake(['api.smartrecruiters.com/*' => Http::response($page)]);

    expect((new SmartRecruitersReader)->read($this->company))->toHaveCount(3);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.smartrecruiters.com/v1/companies/aboutyougmbh/postings?offset=100&limit=100');
});

it('keeps one posting for each job, the English one', function () {
    $postings = JobFields::json('smartrecruiters/postings.json')['content'];

    expect(array_column($postings, 'refNumber'))->toBe(['ID2610-00549A', 'ID2610-00549A', 'ID2609-00478A', 'ID2611-00631A'])
        ->and($postings[0]['language']['code'])->toBe('de')
        ->and($postings[1]['language']['code'])->toBe('en');

    Http::fake(['api.smartrecruiters.com/*' => Http::response(['offset' => 0, 'limit' => 100, 'totalFound' => 4, 'content' => array_reverse($postings)])]);

    expect(array_column((new SmartRecruitersReader)->read($this->company), 'id'))->toBe(['744000161508939', '744000158738024', '744000155066262']);
});

it('keeps every posting that has no reference number', function () {
    $postings = array_map(
        fn (array $posting) => array_diff_key($posting, ['refNumber' => true]),
        JobFields::json('smartrecruiters/postings.json')['content'],
    );

    Http::fake(['api.smartrecruiters.com/*' => Http::response(['offset' => 0, 'limit' => 100, 'totalFound' => 4, 'content' => $postings])]);

    expect((new SmartRecruitersReader)->read($this->company))->toHaveCount(4);
});

it('gives no jobs for an unknown company, as the API does', function () {
    Http::fake(['api.smartrecruiters.com/*' => Http::response(['offset' => 0, 'limit' => 100, 'totalFound' => 0, 'content' => []])]);

    expect((new SmartRecruitersReader)->read($this->company))->toBe([]);
});

it('normalises a hybrid job field by field', function () {
    $job = (new SmartRecruitersReader)->normalise(new AtsItem($this->company, smartRecruitersItem(), $this->fetchedAt));

    expect(JobFields::of($job))->toBe([
        'source' => 'ats',
        'sourceJobId' => 'smartrecruiters:aboutyougmbh:ID2610-00549A',
        'sourceUrl' => 'https://jobs.smartrecruiters.com/ABOUTYOUGmbH/744000155066379',
        'applyUrl' => 'https://jobs.smartrecruiters.com/ABOUTYOUGmbH/744000155066379',
        'title' => 'Senior PHP Engineer - Checkout - Pre-Confirmation (all genders)',
        'company' => 'ABOUT YOU SE & Co. KG',
        'tags' => ['product & tech', 'engineering'],
        'workMode' => 'hybrid',
        'eligibility' => ['type' => 'unknown', 'regions' => [], 'countries' => []],
        'officeLocation' => 'Hamburg, HH, Germany',
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => null,
        'contractType' => 'full_time',
        'seniority' => 'senior',
        'postedAt' => '2026-10-12T16:00:32Z',
        'fetchedAt' => '2026-11-23T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via ABOUT YOU SE & Co. KG careers',
    ])
        ->and($job->description)->toBeNull();
});

it('reads the place, the contract type and the level of each job', function (int $index, array $fields) {
    $job = (new SmartRecruitersReader)->normalise(new AtsItem($this->company, smartRecruitersItem($index), $this->fetchedAt));

    expect(JobFields::parsed($job))->toBe(JobFields::expected($fields));
})->with([
    'hybrid, full-time, senior in the title' => [0, [
        'workMode' => 'hybrid',
        'officeLocation' => 'Hamburg, HH, Germany',
        'contractType' => 'full_time',
        'seniority' => 'senior',
    ]],
    'remote in one country' => [2, [
        'workMode' => 'remote',
        'eligibility' => JobFields::restricted(['US']),
        'contractType' => 'full_time',
    ]],
    'on-site, part-time, entry level' => [3, [
        'workMode' => 'onsite',
        'officeLocation' => 'Hamburg, HH, Germany',
        'contractType' => 'part_time',
        'seniority' => 'junior',
    ]],
]);

it('uses the slug and the display name when the job has no company', function () {
    $company = CompanyEntry::parse('smartrecruiters:aboutyougmbh', 'About You');

    $job = (new SmartRecruitersReader)->normalise(new AtsItem($company, smartRecruitersItem(0, ['company' => null]), $this->fetchedAt));

    expect($job->company)->toBe('About You')
        ->and($job->sourceUrl)->toBe('https://jobs.smartrecruiters.com/aboutyougmbh/744000155066379');
});

it('uses the id when the job has no reference number', function () {
    $job = (new SmartRecruitersReader)->normalise(new AtsItem($this->company, smartRecruitersItem(0, ['refNumber' => null]), $this->fetchedAt));

    expect($job->sourceJobId)->toBe('smartrecruiters:aboutyougmbh:744000155066379');
});

it('gives an unknown work mode when the job has no remote and hybrid fields', function () {
    $job = (new SmartRecruitersReader)->normalise(new AtsItem($this->company, smartRecruitersItem(0, ['location' => ['city' => 'Hamburg', 'country' => 'de']]), $this->fetchedAt));

    expect($job->workMode->value)->toBe('unknown')
        ->and($job->officeLocation)->toBe('Hamburg, DE');
});

it('skips a job that cannot be normalised', function (array $changes) {
    (new SmartRecruitersReader)->normalise(new AtsItem($this->company, smartRecruitersItem(0, $changes), $this->fetchedAt));
})->throws(SkipItem::class)->with([
    'no title' => [['name' => '']],
    'no id' => [['id' => null]],
    'bad date' => [['releasedDate' => 'not a date']],
    'no date' => [['releasedDate' => null]],
]);
