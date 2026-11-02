<?php

namespace App\Sources\WeWorkRemotely;

use App\Parsers\Location\LocationParser;
use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\InvalidJob;
use App\Schema\NormalisedJob;
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
use SimpleXMLElement;
use Throwable;

/**
 * We Work Remotely back-end RSS feed. See the field mapping in docs/DECISIONS.md.
 */
final class WeWorkRemotelySource implements JobSource
{
    private const FEED_URL = 'https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss';

    private const FIELDS = ['guid', 'link', 'title', 'description', 'region', 'country', 'skills', 'category', 'type', 'pubDate', 'expires_at'];

    public function key(): string
    {
        return 'weworkremotely';
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
            throw new UnreadableResponse('The We Work Remotely feed is not RSS.');
        }

        foreach ($xml->channel->item ?? [] as $item) {
            yield $this->toArray($item);
        }
    }

    public function normalise(mixed $raw): NormalisedJob
    {
        if (! is_array($raw)) {
            throw SkipItem::because('The item is not a We Work Remotely item.');
        }

        $link = $this->required($raw, 'link');
        [$company, $title] = $this->companyAndTitle($this->required($raw, 'title'));
        $expiresAt = $this->text($raw, 'expires_at');

        try {
            return new NormalisedJob(
                source: $this->key(),
                sourceJobId: $this->text($raw, 'guid') ?? $link,
                sourceUrl: $link,
                applyUrl: $link,
                title: $title,
                company: $company,
                description: HtmlToText::convert($this->text($raw, 'description')),
                tags: [...$this->skills($this->text($raw, 'skills') ?? ''), $this->text($raw, 'category') ?? ''],
                workMode: WorkMode::Remote,
                eligibility: TitleLimits::eligibility($this->eligibility($this->text($raw, 'country'), $this->text($raw, 'region')), WorkMode::Remote, $title),
                officeLocation: null,
                timezoneMin: null,
                timezoneMax: null,
                salary: null,
                contractType: TitleFallback::contractType(match ($this->text($raw, 'type')) {
                    'Full-Time' => ContractType::FullTime,
                    'Part-Time' => ContractType::PartTime,
                    'Contract' => ContractType::Contract,
                    default => ContractType::Unknown,
                }, $title),
                seniority: TitleFallback::seniority(Seniority::Unknown, $title),
                postedAt: $this->date($this->required($raw, 'pubDate')),
                fetchedAt: CarbonImmutable::now(),
                expiresAt: $expiresAt === null ? null : $this->date($expiresAt),
                attribution: $this->attribution(),
            );
        } catch (InvalidJob $invalid) {
            throw SkipItem::because($invalid->getMessage());
        }
    }

    public function attribution(): string
    {
        return 'via We Work Remotely';
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
        $fields = [];

        foreach (self::FIELDS as $field) {
            $fields[$field] = (string) $item->{$field};
        }

        return $fields;
    }

    /**
     * The country list wins over the region, which often says "Anywhere in the
     * World" even for European jobs. An empty country list and "Anywhere in the
     * World" give worldwide. Another region is free text.
     */
    private function eligibility(?string $country, ?string $region): Eligibility
    {
        if ($country !== null) {
            return (new LocationParser)->countryText($country);
        }

        return match ($region) {
            null => Eligibility::unknown(),
            'Anywhere in the World' => Eligibility::worldwide(),
            default => (new LocationParser)->parse($region)->eligibility,
        };
    }

    /**
     * "Company: Title", split at the first ": ".
     *
     * @return array{string, string}
     */
    private function companyAndTitle(string $text): array
    {
        $parts = explode(': ', $text, 2);
        $company = trim($parts[0]);
        $title = trim($parts[1] ?? '');

        if ($company === '' || $title === '') {
            throw SkipItem::because("The title has no \"Company: Title\" form: \"{$text}\".");
        }

        return [$company, $title];
    }

    /**
     * "Java, PostgreSQL, and Spring" or "Django and Python".
     *
     * @return list<string>
     */
    private function skills(string $skills): array
    {
        return preg_split('/\s*,\s*(?:and\s+)?|\s+and\s+/', $skills) ?: [];
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

    /**
     * RFC 822, e.g. "Thu, 17 Sep 2026 10:51:23 +0000".
     */
    private function date(string $date): CarbonImmutable
    {
        try {
            $parsed = CarbonImmutable::createFromFormat(DATE_RSS, $date);
        } catch (Throwable) {
            $parsed = null;
        }

        return $parsed?->utc() ?? throw SkipItem::because("The job has a bad date: \"{$date}\".");
    }
}
