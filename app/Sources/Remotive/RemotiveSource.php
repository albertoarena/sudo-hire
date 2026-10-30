<?php

namespace App\Sources\Remotive;

use App\Parsers\Location\LocationParser;
use App\Parsers\Salary\SalaryParser;
use App\Schema\ContractType;
use App\Schema\InvalidJob;
use App\Schema\NormalisedJob;
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
use Throwable;

/**
 * Remotive remote jobs API. See the field mapping in docs/DECISIONS.md.
 * Remotive terms: link back to the Remotive job page and name Remotive as the source.
 */
final class RemotiveSource implements JobSource
{
    private const API_URL = 'https://remotive.com/api/remote-jobs';

    public function key(): string
    {
        return 'remotive';
    }

    /**
     * One request gives all matches, so $since is not used.
     *
     * @return iterable<array<mixed>>
     */
    public function fetch(?CarbonInterface $since = null): iterable
    {
        $jobs = Http::timeout(20)->get(self::API_URL, ['search' => 'php'])->throw()->json('jobs');

        if (! is_array($jobs) || ! array_is_list($jobs)) {
            throw new UnreadableResponse('The Remotive response has no job list.');
        }

        yield from array_values(array_filter($jobs, 'is_array'));
    }

    public function normalise(mixed $raw): NormalisedJob
    {
        if (! is_array($raw)) {
            throw SkipItem::because('The item is not a Remotive job.');
        }

        $url = $this->required($raw, 'url');
        $title = $this->required($raw, 'title');
        $location = (new LocationParser)->parse($this->text($raw, 'candidate_required_location') ?? '');
        $salary = $this->text($raw, 'salary');

        try {
            return new NormalisedJob(
                source: $this->key(),
                sourceJobId: $this->required($raw, 'id'),
                // Remotive terms: the apply link is the Remotive job page.
                sourceUrl: $url,
                applyUrl: $url,
                title: $title,
                company: $this->required($raw, 'company_name'),
                description: HtmlToText::convert($this->text($raw, 'description')),
                tags: $this->list($raw, 'tags'),
                workMode: WorkMode::Remote,
                // The work mode stays remote: Remotive lists remote jobs only.
                eligibility: $location->eligibility,
                officeLocation: null,
                timezoneMin: $location->timezoneMin,
                timezoneMax: $location->timezoneMax,
                salary: $salary === null ? null : (new SalaryParser)->parse($salary),
                contractType: TitleFallback::contractType(match ($this->text($raw, 'job_type')) {
                    'full_time' => ContractType::FullTime,
                    'part_time' => ContractType::PartTime,
                    'contract', 'freelance' => ContractType::Contract,
                    default => ContractType::Unknown,
                }, $title),
                seniority: TitleFallback::seniority(Seniority::Unknown, $title),
                postedAt: $this->postedAt($this->required($raw, 'publication_date')),
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
        return 'via Remotive';
    }

    /**
     * Remotive advises at most 4 requests a day.
     */
    public function minInterval(): CarbonInterval
    {
        return CarbonInterval::hours(6);
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
     * The date has no timezone: it is UTC.
     */
    private function postedAt(string $date): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($date, 'UTC')->utc();
        } catch (Throwable) {
            throw SkipItem::because("The job has a bad date: \"{$date}\".");
        }
    }
}
