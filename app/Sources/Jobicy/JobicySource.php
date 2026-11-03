<?php

namespace App\Sources\Jobicy;

use App\Parsers\Location\LocationParser;
use App\Schema\ContractType;
use App\Schema\InvalidJob;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use App\Sources\JobSource;
use App\Sources\SkipItem;
use App\Sources\Support\HtmlToText;
use App\Sources\Support\TitleFallback;
use App\Sources\Support\TitleLimits;
use App\Sources\UnreadableResponse;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Jobicy remote jobs API. See the field mapping in docs/DECISIONS.md.
 * Jobicy terms: credit Jobicy and keep its job link on every apply button.
 */
final class JobicySource implements JobSource
{
    private const API_URL = 'https://jobicy.com/api/jobs';

    private const PERIODS = [
        'yearly' => SalaryPeriod::Year,
        'monthly' => SalaryPeriod::Month,
        'daily' => SalaryPeriod::Day,
        'hourly' => SalaryPeriod::Hour,
    ];

    public function key(): string
    {
        return 'jobicy';
    }

    /**
     * One request gives the newest 100 engineering jobs, so $since is not used.
     *
     * @return iterable<array<mixed>>
     */
    public function fetch(?CarbonInterface $since = null): iterable
    {
        $jobs = Http::timeout(20)->get(self::API_URL, ['count' => 100, 'industry' => 'engineering'])->throw()->json('jobs');

        if (! is_array($jobs) || ! array_is_list($jobs)) {
            throw new UnreadableResponse('The Jobicy response has no job list.');
        }

        yield from array_values(array_filter($jobs, 'is_array'));
    }

    public function normalise(mixed $raw): NormalisedJob
    {
        if (! is_array($raw)) {
            throw SkipItem::because('The item is not a Jobicy job.');
        }

        $url = $this->required($raw, 'url');
        $title = $this->required($raw, 'jobTitle');
        $location = (new LocationParser)->parse($this->text($raw, 'jobGeo') ?? '');

        try {
            return new NormalisedJob(
                source: $this->key(),
                sourceJobId: $this->required($raw, 'id'),
                // Jobicy terms: the apply link is the Jobicy job page.
                sourceUrl: $url,
                applyUrl: $url,
                title: $title,
                company: $this->required($raw, 'companyName'),
                description: HtmlToText::convert($this->text($raw, 'jobDescription')),
                tags: $this->list($raw, 'jobIndustry'),
                workMode: WorkMode::Remote,
                // jobGeo is free text. The work mode stays remote: Jobicy lists remote jobs only.
                eligibility: TitleLimits::eligibility($location->eligibility, WorkMode::Remote, $title),
                officeLocation: null,
                timezoneMin: $location->timezoneMin,
                timezoneMax: $location->timezoneMax,
                salary: $this->salary($raw),
                contractType: TitleFallback::contractType(match ($this->list($raw, 'jobType')[0] ?? null) {
                    'Full-Time' => ContractType::FullTime,
                    'Part-Time' => ContractType::PartTime,
                    'Contract' => ContractType::Contract,
                    default => ContractType::Unknown,
                }, $title),
                seniority: TitleFallback::seniority(match ($this->text($raw, 'jobLevel')) {
                    'Junior', 'Entry-Level, Junior' => Seniority::Junior,
                    'Midweight' => Seniority::Mid,
                    'Senior' => Seniority::Senior,
                    'Manager', 'Director' => Seniority::Lead,
                    default => Seniority::Unknown,
                }, $title),
                postedAt: $this->postedAt($this->required($raw, 'pubDate')),
                fetchedAt: CarbonImmutable::now(),
                expiresAt: null,
                attribution: $this->attribution(),
            );
        } catch (InvalidJob $invalid) {
            throw SkipItem::because($invalid->getMessage());
        }
    }

    public function attribution(): string
    {
        return 'via Jobicy';
    }

    public function minInterval(): CarbonInterval
    {
        // Jobicy fair use: a few reads a day, never more than one an hour.
        return CarbonInterval::hours(6);
    }

    /**
     * The salary fields are absent when the salary is unknown. A bad
     * value gives no salary, not a skipped job.
     *
     * @param  array<mixed>  $raw
     */
    private function salary(array $raw): ?Salary
    {
        $min = is_numeric($raw['salaryMin'] ?? null) ? (float) $raw['salaryMin'] : null;
        $max = is_numeric($raw['salaryMax'] ?? null) ? (float) $raw['salaryMax'] : null;
        $currency = $this->text($raw, 'salaryCurrency');
        $period = $this->text($raw, 'salaryPeriod');

        if (($min === null && $max === null) || $currency === null) {
            return null;
        }

        $amount = implode(' - ', array_filter([$this->text($raw, 'salaryMin'), $this->text($raw, 'salaryMax')]));

        try {
            return new Salary($min, $max, $currency, self::PERIODS[$period] ?? SalaryPeriod::Unknown, trim("{$amount} {$currency} {$period}"));
        } catch (InvalidJob) {
            return null;
        }
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function text(array $raw, string $field): ?string
    {
        $value = $raw[$field] ?? null;
        $text = is_string($value) || is_int($value) || is_float($value) ? trim((string) $value) : '';

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function required(array $raw, string $field): string
    {
        return $this->text($raw, $field) ?? throw SkipItem::because("The job has no {$field}.");
    }

    /**
     * @param  array<mixed>  $raw
     * @return list<string>
     */
    private function list(array $raw, string $field): array
    {
        $values = is_array($raw[$field] ?? null) ? $raw[$field] : [];

        return array_values(array_filter(array_map(
            fn (mixed $value) => is_string($value) ? trim($value) : '',
            $values,
        )));
    }

    private function postedAt(string $date): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($date)->utc();
        } catch (Throwable) {
            throw SkipItem::because("The job has a bad date: \"{$date}\".");
        }
    }
}
