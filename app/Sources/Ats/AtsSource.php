<?php

namespace App\Sources\Ats;

use App\Schema\NormalisedJob;
use App\Sources\Ats\Readers\AshbyReader;
use App\Sources\Ats\Readers\GreenhouseReader;
use App\Sources\Ats\Readers\LeverReader;
use App\Sources\Ats\Readers\PersonioReader;
use App\Sources\JobSource;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * The ATS reader: one job source for the career pages of the companies in
 * the company list. A failing company does not stop the others.
 */
final class AtsSource implements JobSource
{
    public const KEY = 'ats';

    public const TIMEOUT_SECONDS = 20;

    /** @var array<string, AtsReader> */
    private array $readers = [];

    /**
     * @param  list<AtsReader>|null  $readers  default: one reader for each ATS
     */
    public function __construct(private CompanyList $companies, ?array $readers = null)
    {
        foreach ($readers ?? [new GreenhouseReader, new LeverReader, new AshbyReader, new PersonioReader] as $reader) {
            $this->readers[$reader->ats()->value] = $reader;
        }
    }

    public function key(): string
    {
        return self::KEY;
    }

    /**
     * Each company has its own interval: a company read less than 6 hours
     * ago is skipped. $since is not used: the ATS APIs have no paging.
     *
     * @return iterable<AtsItem>
     */
    public function fetch(?CarbonInterface $since = null): iterable
    {
        foreach ($this->companies->companies() as $company) {
            $now = CarbonImmutable::now();
            $lastCheckedAt = $this->companies->lastCheckedAt($company);

            if ($lastCheckedAt !== null && $lastCheckedAt->gt($now->sub($this->minInterval()))) {
                continue;
            }

            try {
                $items = $this->reader($company->ats)->read($company);
            } catch (ReadFailed $failed) {
                Log::warning("ATS reader: {$company->key()} failed: {$failed->getMessage()}");
                $this->companies->recordError($company, $failed->getMessage(), $now);

                continue;
            }

            $this->companies->recordJobs($company, count($items), $now);

            foreach ($items as $data) {
                yield new AtsItem($company, $data, $now);
            }
        }
    }

    public function normalise(mixed $raw): NormalisedJob
    {
        if (! $raw instanceof AtsItem) {
            throw SkipItem::because('The item is not an ATS item.');
        }

        return $this->reader($raw->company->ats)->normalise($raw);
    }

    /**
     * Each job has its own text, "via <Company> careers". This is the
     * text for the source as a whole.
     */
    public function attribution(): string
    {
        return 'via company careers';
    }

    public function minInterval(): CarbonInterval
    {
        return CarbonInterval::hours(6);
    }

    private function reader(Ats $ats): AtsReader
    {
        return $this->readers[$ats->value]
            ?? throw new LogicException("No reader for the ATS \"{$ats->value}\".");
    }
}
