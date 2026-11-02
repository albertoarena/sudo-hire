<?php

namespace App\Sources\LaraJobs;

use App\Parsers\Location\LocationParser;
use App\Parsers\Salary\SalaryParser;
use App\Schema\ContractType;
use App\Schema\InvalidJob;
use App\Schema\NormalisedJob;
use App\Schema\Seniority;
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
use SimpleXMLElement;
use Throwable;

/**
 * LaraJobs RSS feed. See the field mapping in docs/DECISIONS.md.
 */
final class LaraJobsSource implements JobSource
{
    private const FEED_URL = 'https://larajobs.com/feed';

    private const NAMESPACES = [
        'job' => 'https://larajobs.com',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'content' => 'http://purl.org/rss/1.0/modules/content/',
    ];

    public function key(): string
    {
        return 'larajobs';
    }

    /**
     * The feed has no paging, so $since is not used.
     *
     * @return iterable<array<string, string>>
     */
    public function fetch(?CarbonInterface $since = null): iterable
    {
        $body = Http::timeout(20)->get(self::FEED_URL)->throw()->body();

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, options: LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false || $xml->getName() !== 'rss') {
            throw new UnreadableResponse('The LaraJobs feed is not RSS.');
        }

        foreach ($xml->channel->item ?? [] as $item) {
            yield $this->toArray($item);
        }
    }

    public function normalise(mixed $raw): NormalisedJob
    {
        if (! is_array($raw)) {
            throw SkipItem::because('The item is not a LaraJobs item.');
        }

        $link = $this->required($raw, 'link');
        $title = $this->required($raw, 'title');
        $location = (new LocationParser)->parse($this->text($raw, 'location') ?? '');
        $salary = $this->text($raw, 'salary');

        try {
            return new NormalisedJob(
                source: $this->key(),
                sourceJobId: $this->text($raw, 'guid') ?? $link,
                sourceUrl: $link,
                applyUrl: $link,
                title: $title,
                company: $this->text($raw, 'company') ?? $this->text($raw, 'creator') ?? throw SkipItem::because('The job has no company.'),
                description: HtmlToText::convert($this->text($raw, 'content') ?? $this->text($raw, 'description')),
                tags: explode(',', $this->text($raw, 'tags') ?? ''),
                workMode: $location->workMode,
                eligibility: TitleLimits::eligibility($location->eligibility, $location->workMode, $title),
                officeLocation: $location->officeLocation,
                timezoneMin: $location->timezoneMin,
                timezoneMax: $location->timezoneMax,
                salary: $salary === null ? null : (new SalaryParser)->parse($salary),
                contractType: TitleFallback::contractType(match ($this->text($raw, 'job_type')) {
                    'FULL_TIME' => ContractType::FullTime,
                    'PART_TIME' => ContractType::PartTime,
                    'CONTRACT' => ContractType::Contract,
                    default => ContractType::Unknown,
                }, $title),
                seniority: TitleFallback::seniority(Seniority::Unknown, $title),
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
        return 'via LaraJobs';
    }

    public function minInterval(): CarbonInterval
    {
        return CarbonInterval::hours(2);
    }

    /**
     * @return array<string, string>
     */
    private function toArray(SimpleXMLElement $item): array
    {
        $job = $item->children(self::NAMESPACES['job']);

        return [
            'guid' => (string) $item->guid,
            'link' => (string) $item->link,
            'title' => (string) $item->title,
            'pubDate' => (string) $item->pubDate,
            'creator' => (string) $item->children(self::NAMESPACES['dc'])->creator,
            'description' => (string) $item->description,
            'content' => (string) $item->children(self::NAMESPACES['content'])->encoded,
            'company' => (string) $job->company,
            'location' => (string) $job->location,
            'job_type' => (string) $job->job_type,
            'salary' => (string) $job->salary,
            'tags' => (string) $job->tags,
        ];
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function text(array $raw, string $field): ?string
    {
        $value = $raw[$field] ?? null;
        $text = is_string($value) ? trim($value) : '';

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function required(array $raw, string $field): string
    {
        return $this->text($raw, $field) ?? throw SkipItem::because("The job has no {$field}.");
    }

    private function postedAt(string $date): CarbonImmutable
    {
        try {
            $postedAt = CarbonImmutable::createFromFormat(DATE_RSS, $date);
        } catch (Throwable) {
            $postedAt = null;
        }

        return $postedAt?->utc() ?? throw SkipItem::because("The job has a bad date: \"{$date}\".");
    }
}
