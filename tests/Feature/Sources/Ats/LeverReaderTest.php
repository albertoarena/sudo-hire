<?php

use App\Sources\Ats\AtsItem;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\Readers\LeverReader;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

beforeEach(function () {
    $this->company = CompanyEntry::parse('lever:spotify', 'Spotify');
    $this->fetchedAt = CarbonImmutable::parse('2026-10-29T10:00:00Z');
});

const LEVER_BASE_RANGE = "The United States base range for this position is \$164,448\u{2013}\$234,926 USD, plus equity.";

function leverItem(int $index = 0, array $changes = []): array
{
    return array_merge(JobFields::json('lever/postings.json')[$index], $changes);
}

it('reads the postings from the postings API', function () {
    Http::fake(['api.lever.co/*' => Http::response(JobFields::fixture('lever/postings.json'))]);

    $items = (new LeverReader)->read($this->company);

    expect($items)->toHaveCount(3)
        ->and($items[0]['id'])->toBe('c73be71c-1a63-4906-9925-95f2115d1f1d');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.lever.co/v0/postings/spotify?mode=json');
});

it('normalises a remote job field by field', function () {
    $job = (new LeverReader)->normalise(new AtsItem($this->company, leverItem(0), $this->fetchedAt));

    expect(JobFields::of($job))->toBe([
        'source' => 'ats',
        'sourceJobId' => 'lever:spotify:c73be71c-1a63-4906-9925-95f2115d1f1d',
        'sourceUrl' => 'https://jobs.lever.co/spotify/c73be71c-1a63-4906-9925-95f2115d1f1d',
        'applyUrl' => 'https://jobs.lever.co/spotify/c73be71c-1a63-4906-9925-95f2115d1f1d/apply',
        'title' => 'Senior Software Engineer - Enterprise AI',
        'company' => 'Spotify',
        'tags' => ['platform', 'engineering'],
        'workMode' => 'remote',
        'eligibility' => ['type' => 'restricted', 'regions' => [], 'countries' => ['US']],
        'officeLocation' => null,
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => ['min' => 164448.0, 'max' => 234926.0, 'currency' => 'USD', 'period' => 'unknown', 'raw' => LEVER_BASE_RANGE],
        'contractType' => 'full_time',
        'seniority' => 'senior',
        'postedAt' => '2026-08-13T18:12:43Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via Spotify careers',
    ]);
});

it('joins the description, the lists and the additional text in this order', function () {
    $job = (new LeverReader)->normalise(new AtsItem($this->company, leverItem(0), $this->fetchedAt));

    $description = (string) $job->description;

    expect($description)
        ->toStartWith('The Platform team creates the technology that enables Spotify')
        ->toContain("What You'll Do\n- Design, build, and operate secure enterprise AI platform capabilities")
        ->toContain("Where You'll Be\n- This role is based in New York.")
        ->toEndWith('Our platform is for everyone, and so is our workplace. The more voices we have')
        ->not->toContain('<li>')
        ->and(strpos($description, 'Who You Are'))->toBeLessThan(strpos($description, 'The United States base range'));
});

it('uses the HTML description when the plain one is empty', function () {
    $job = (new LeverReader)->normalise(new AtsItem($this->company, leverItem(1), $this->fetchedAt));

    expect($job->description)
        ->toStartWith('We design Spotify’s consumer experience')
        ->not->toContain('<p>');
});

it('takes the company name from the company entry', function () {
    $job = (new LeverReader)->normalise(new AtsItem(CompanyEntry::parse('lever:spotify', 'Spotify AB'), leverItem(0), $this->fetchedAt));

    expect($job->company)->toBe('Spotify AB')
        ->and($job->attribution)->toBe('via Spotify AB careers');
});

it('gives the office locations of a hybrid job and no eligibility', function () {
    $job = (new LeverReader)->normalise(new AtsItem($this->company, leverItem(1), $this->fetchedAt));

    expect($job->workMode->value)->toBe('hybrid')
        ->and($job->officeLocation)->toBe('London; Stockholm')
        ->and($job->eligibility->type->value)->toBe('unknown');
});

it('maps the workplace type', function (string $type, string $mode) {
    $job = (new LeverReader)->normalise(new AtsItem($this->company, leverItem(2, ['workplaceType' => $type]), $this->fetchedAt));

    expect($job->workMode->value)->toBe($mode);
})->with([
    ['remote', 'remote'],
    ['hybrid', 'hybrid'],
    ['onsite', 'onsite'],
    ['on-site', 'onsite'],
    ['unspecified', 'unknown'],
]);

it('maps the commitment to the contract type', function (?string $commitment, string $contract) {
    $item = leverItem(0);
    $item['categories']['commitment'] = $commitment;

    $job = (new LeverReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt));

    expect($job->contractType->value)->toBe($contract);
})->with([
    ['Permanent', 'full_time'],
    ['Full-time', 'full_time'],
    ['Part-time', 'part_time'],
    ['Contract', 'contract'],
    ['None', 'unknown'],
    [null, 'unknown'],
]);

it('reads the salary text and the title of each job', function (int $index, array $fields) {
    $job = (new LeverReader)->normalise(new AtsItem($this->company, leverItem($index), $this->fetchedAt));

    expect(array_intersect_key(JobFields::parsed($job), $fields))->toBe($fields);
})->with([
    'a base range in the additional text' => [0, [
        'salary' => ['min' => 164448.0, 'max' => 234926.0, 'currency' => 'USD', 'period' => 'unknown', 'raw' => LEVER_BASE_RANGE],
        'seniority' => 'senior',
    ]],
    'no additional text' => [1, ['salary' => null, 'seniority' => 'senior']],
    'additional text with no amount' => [2, ['salary' => null, 'seniority' => 'unknown']],
]);

it('keeps the salary range over the salary text', function () {
    $company = CompanyEntry::parse('lever:matchgroup', 'Match Group');
    $item = JobFields::json('lever/postings-salary.json')[0];
    $item['additionalPlain'] = LEVER_BASE_RANGE;

    $job = (new LeverReader)->normalise(new AtsItem($company, $item, $this->fetchedAt));

    expect($job->salary?->raw)->toBe('150000 - 180000 USD per-year-salary');
});

it('takes the contract type from the title when the commitment says nothing', function () {
    $item = leverItem(0, ['text' => 'Recruiting Coordinator (Contract)', 'categories' => ['commitment' => 'None']]);

    expect((new LeverReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt))->contractType->value)->toBe('contract');
});

it('gives a remote job with no country an unknown eligibility', function () {
    $job = (new LeverReader)->normalise(new AtsItem($this->company, leverItem(0, ['country' => null]), $this->fetchedAt));

    expect($job->eligibility->type->value)->toBe('unknown');
});

it('uses the salary range when the posting has one', function (string $interval, string $period) {
    $item = leverItem(0, ['salaryRange' => ['min' => 164448, 'max' => 234926, 'currency' => 'USD', 'interval' => $interval]]);

    $job = (new LeverReader)->normalise(new AtsItem($this->company, $item, $this->fetchedAt));

    expect(JobFields::of($job)['salary'])->toBe([
        'min' => 164448.0,
        'max' => 234926.0,
        'currency' => 'USD',
        'period' => $period,
        'raw' => "164448 - 234926 USD {$interval}",
    ]);
})->with([
    ['per-year-salary', 'year'],
    ['per-month-salary', 'month'],
    ['per-day-wage', 'day'],
    ['per-hour-wage', 'hour'],
    ['one-time', 'unknown'],
]);

it('reads the salary range of real postings', function (int $index, array $salary) {
    $company = CompanyEntry::parse('lever:matchgroup', 'Match Group');
    $item = JobFields::json('lever/postings-salary.json')[$index];

    $job = (new LeverReader)->normalise(new AtsItem($company, $item, $this->fetchedAt));

    expect(JobFields::of($job)['salary'])->toBe($salary);
})->with([
    'per year' => [0, ['min' => 150000.0, 'max' => 180000.0, 'currency' => 'USD', 'period' => 'year', 'raw' => '150000 - 180000 USD per-year-salary']],
    'per hour' => [1, ['min' => 35.0, 'max' => 38.0, 'currency' => 'CAD', 'period' => 'hour', 'raw' => '35 - 38 CAD per-hour-wage']],
]);

it('gives no salary for a salary range with no amount or currency', function (array $range) {
    // No additional text, so the salary text cannot give a salary either.
    $job = (new LeverReader)->normalise(new AtsItem($this->company, leverItem(0, ['salaryRange' => $range, 'additionalPlain' => '']), $this->fetchedAt));

    expect($job->salary)->toBeNull();
})->with([
    'no amount' => [['currency' => 'USD', 'interval' => 'per-year-salary']],
    'no currency' => [['min' => 1000, 'max' => 2000, 'interval' => 'per-year-salary']],
]);

it('skips a job that cannot be normalised', function (array $changes) {
    (new LeverReader)->normalise(new AtsItem($this->company, leverItem(0, $changes), $this->fetchedAt));
})->throws(SkipItem::class)->with([
    'no title' => [['text' => '']],
    'no link' => [['hostedUrl' => null]],
    'no id' => [['id' => null]],
    'no date' => [['createdAt' => null]],
]);
