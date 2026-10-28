<?php

namespace App\Schema;

use Carbon\CarbonImmutable;

/**
 * One job in the shared schema. See docs/REQUIREMENTS.md section 5.
 * Changes need approval: premium sources depend on it (AGENTS.md rule 10).
 */
final readonly class NormalisedJob
{
    /** @var list<string> */
    public array $tags;

    public string $fingerprint;

    /**
     * @param  list<string>  $tags
     * @param  float|null  $timezoneMin  UTC offset in hours, e.g. -9.5
     * @param  float|null  $timezoneMax  UTC offset in hours, e.g. 5.75
     */
    public function __construct(
        public string $source,
        public string $sourceJobId,
        public string $sourceUrl,
        public string $applyUrl,
        public string $title,
        public string $company,
        public ?string $description,
        array $tags,
        public WorkMode $workMode,
        public Eligibility $eligibility,
        public ?string $officeLocation,
        public ?float $timezoneMin,
        public ?float $timezoneMax,
        public ?Salary $salary,
        public ContractType $contractType,
        public Seniority $seniority,
        public CarbonImmutable $postedAt,
        public CarbonImmutable $fetchedAt,
        public ?CarbonImmutable $expiresAt,
        public string $attribution,
    ) {
        foreach (['source', 'sourceJobId', 'sourceUrl', 'applyUrl', 'title', 'company'] as $field) {
            if (trim($this->{$field}) === '') {
                throw new InvalidJob("Job field \"{$field}\" is required.");
            }
        }

        foreach (['sourceUrl', 'applyUrl'] as $field) {
            if (preg_match('#^https?://[^\s/]+#i', $this->{$field}) !== 1) {
                throw new InvalidJob("Job field \"{$field}\" must be an http or https link.");
            }
        }

        foreach ([$timezoneMin, $timezoneMax] as $offset) {
            if ($offset !== null && ($offset < -12 || $offset > 14)) {
                throw new InvalidJob("Timezone offset must be between -12 and 14, got {$offset}.");
            }
        }

        if ($timezoneMin !== null && $timezoneMax !== null && $timezoneMin > $timezoneMax) {
            throw new InvalidJob('Timezone min cannot be above max.');
        }

        if ($expiresAt !== null && $expiresAt->lt($postedAt)) {
            throw new InvalidJob('Expiry date cannot be before the posted date.');
        }

        $this->tags = array_values(array_unique(array_filter(
            array_map(fn (string $tag) => mb_strtolower(trim($tag)), $tags),
            fn (string $tag) => $tag !== '',
        )));

        $this->fingerprint = sha1(self::normalise($company).'|'.self::normalise($title));
    }

    private static function normalise(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }
}
