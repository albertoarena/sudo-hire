<?php

namespace App\Sources\Himalayas;

use App\Parsers\Location\LocationParser;
use App\Schema\ContractType;
use App\Schema\Eligibility;
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
use App\Sources\UnreadableResponse;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Http;

/**
 * Himalayas search API. See the field mapping in docs/DECISIONS.md.
 * Himalayas terms: credit Himalayas, link back and keep the apply link with its UTM parameter.
 */
final class HimalayasSource implements JobSource
{
    private const SEARCH_URL = 'https://himalayas.app/jobs/api/search';

    private const QUERIES = ['php', 'laravel'];

    private const PAGE_SIZE = 20;

    private const MAX_PAGES = 5;

    private const PERIODS = [
        'annual' => SalaryPeriod::Year,
        'monthly' => SalaryPeriod::Month,
    ];

    /** Lowest level first: the lowest level in a job's list wins. */
    private const SENIORITY = [
        'Entry-level' => Seniority::Junior,
        'Junior' => Seniority::Junior,
        'Mid-level' => Seniority::Mid,
        'Senior' => Seniority::Senior,
        'Manager' => Seniority::Lead,
        'Director' => Seniority::Lead,
        'Executive' => Seniority::Lead,
    ];

    public function key(): string
    {
        return 'himalayas';
    }

    /**
     * Search is sorted by relevance, not by date. Each search stops at a page
     * that is not full, at a page with no job newer than $since, or after
     * MAX_PAGES. A job found by both searches is read once.
     *
     * @return iterable<array<mixed>>
     */
    public function fetch(?CarbonInterface $since = null): iterable
    {
        $seen = [];

        foreach (self::QUERIES as $query) {
            for ($page = 1; $page <= self::MAX_PAGES; $page++) {
                $jobs = $this->page($query, $page);
                $hasNewer = false;

                foreach ($jobs as $job) {
                    // Before the duplicate check: a page of jobs seen in an earlier search still counts as new.
                    $hasNewer = $hasNewer || $since === null || ! is_numeric($job['pubDate'] ?? null) || (int) $job['pubDate'] >= $since->getTimestamp();
                    $guid = is_string($job['guid'] ?? null) ? $job['guid'] : null;

                    if ($guid !== null) {
                        if (isset($seen[$guid])) {
                            continue;
                        }

                        $seen[$guid] = true;
                    }

                    yield $job;
                }

                if (count($jobs) < self::PAGE_SIZE || ! $hasNewer) {
                    break;
                }
            }
        }
    }

    public function normalise(mixed $raw): NormalisedJob
    {
        if (! is_array($raw)) {
            throw SkipItem::because('The item is not a Himalayas job.');
        }

        $guid = $this->required($raw, 'guid');
        $title = $this->required($raw, 'title');
        $timezones = $this->timezones($this->numbers($raw, 'timezoneRestrictions'));
        $locations = $this->list($raw, 'locationRestrictions');
        $expiryDate = $this->text($raw, 'expiryDate');

        try {
            return new NormalisedJob(
                source: $this->key(),
                sourceJobId: $guid,
                sourceUrl: $guid,
                // Himalayas terms: keep the apply link as it is, with its UTM parameter.
                applyUrl: $this->text($raw, 'applicationLink') ?? $guid,
                title: $title,
                company: $this->required($raw, 'companyName'),
                description: HtmlToText::convert($this->text($raw, 'description')),
                tags: array_map(
                    fn (string $slug) => str_replace('-', ' ', $slug),
                    [...$this->list($raw, 'categories'), ...$this->list($raw, 'parentCategories')],
                ),
                workMode: WorkMode::Remote,
                eligibility: $locations === [] ? Eligibility::worldwide() : (new LocationParser)->countryList($locations),
                officeLocation: null,
                timezoneMin: $timezones === [] ? null : min($timezones),
                timezoneMax: $timezones === [] ? null : max($timezones),
                salary: $this->salary($raw),
                contractType: TitleFallback::contractType(match ($this->text($raw, 'employmentType')) {
                    'Full Time' => ContractType::FullTime,
                    'Part Time' => ContractType::PartTime,
                    'Contractor' => ContractType::Contract,
                    default => ContractType::Unknown,
                }, $title),
                seniority: TitleFallback::seniority($this->seniority($this->list($raw, 'seniority')), $title),
                postedAt: $this->date($this->required($raw, 'pubDate')),
                fetchedAt: CarbonImmutable::now(),
                expiresAt: $expiryDate === null ? null : $this->date($expiryDate),
                attribution: $this->attribution(),
            );
        } catch (InvalidJob $invalid) {
            throw SkipItem::because($invalid->getMessage());
        }
    }

    public function attribution(): string
    {
        return 'via Himalayas';
    }

    public function minInterval(): CarbonInterval
    {
        return CarbonInterval::hours(2);
    }

    /**
     * @return list<array<mixed>>
     */
    private function page(string $query, int $page): array
    {
        $jobs = Http::timeout(20)->get(self::SEARCH_URL, ['q' => $query, 'page' => $page])->throw()->json('jobs');

        if (! is_array($jobs) || ! array_is_list($jobs)) {
            throw new UnreadableResponse('The Himalayas response has no job list.');
        }

        return array_values(array_filter($jobs, 'is_array'));
    }

    /**
     * Only when min or max is set. A bad currency gives no salary, not a skipped job.
     *
     * @param  array<mixed>  $raw
     */
    private function salary(array $raw): ?Salary
    {
        $min = is_numeric($raw['minSalary'] ?? null) ? (float) $raw['minSalary'] : null;
        $max = is_numeric($raw['maxSalary'] ?? null) ? (float) $raw['maxSalary'] : null;
        $currency = $this->text($raw, 'currency');
        $period = $this->text($raw, 'salaryPeriod');

        if (($min === null && $max === null) || $currency === null) {
            return null;
        }

        $amount = implode(' - ', array_filter([$this->text($raw, 'minSalary'), $this->text($raw, 'maxSalary')]));

        try {
            return new Salary($min, $max, $currency, self::PERIODS[$period] ?? SalaryPeriod::Unknown, trim("{$amount} {$currency} {$period}"));
        } catch (InvalidJob) {
            return null;
        }
    }

    /**
     * @param  list<string>  $levels
     */
    private function seniority(array $levels): Seniority
    {
        foreach (self::SENIORITY as $level => $seniority) {
            if (in_array($level, $levels, true)) {
                return $seniority;
            }
        }

        return Seniority::Unknown;
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function text(array $raw, string $field): ?string
    {
        $value = $raw[$field] ?? null;
        $text = is_string($value) || is_int($value) ? trim((string) $value) : '';

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

    /**
     * @param  array<mixed>  $raw
     * @return list<float>
     */
    private function numbers(array $raw, string $field): array
    {
        $values = is_array($raw[$field] ?? null) ? $raw[$field] : [];

        return array_values(array_map(
            fn (int|float $value) => (float) $value,
            array_filter($values, fn (mixed $value) => is_int($value) || is_float($value)),
        ));
    }

    /**
     * The list can wrap past UTC+12: [-10, ..., -5, 14] is one span, because
     * UTC+14 is the same clock time as UTC-10. An offset above 12 becomes the
     * offset minus 24. A list with every whole offset from -11 to +12 is
     * worldwide and stays as it is.
     *
     * @param  list<float>  $offsets
     * @return list<float>
     */
    private function timezones(array $offsets): array
    {
        if (array_diff(range(-11, 12), $offsets) === []) {
            return $offsets;
        }

        return array_map(fn (float $offset) => $offset > 12 ? $offset - 24 : $offset, $offsets);
    }

    /**
     * Unix seconds.
     */
    private function date(string $timestamp): CarbonImmutable
    {
        if (preg_match('/^\d+$/', $timestamp) !== 1) {
            throw SkipItem::because("The job has a bad date: \"{$timestamp}\".");
        }

        return CarbonImmutable::createFromTimestampUTC((int) $timestamp);
    }
}
