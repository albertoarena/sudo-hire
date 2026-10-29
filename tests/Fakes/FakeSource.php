<?php

namespace Tests\Fakes;

use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use App\Sources\JobSource;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;

/**
 * An in-memory source for contract tests. Each raw item is an array.
 * An item without a title cannot be normalised and throws SkipItem.
 */
final class FakeSource implements JobSource
{
    /** @var list<CarbonInterface|null> */
    public array $fetchedSince = [];

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(private array $items = [])
    {
        if ($items === []) {
            $this->items = [
                ['id' => '1', 'title' => 'Senior Laravel Developer', 'company' => 'Acme', 'posted' => '2026-10-27T09:00:00Z'],
                ['id' => '2', 'title' => '', 'company' => 'Broken Ltd', 'posted' => '2026-10-27T10:00:00Z'],
                ['id' => '3', 'title' => 'PHP Engineer', 'company' => 'Globex', 'posted' => '2026-10-26T08:00:00Z'],
            ];
        }
    }

    public function key(): string
    {
        return 'fake';
    }

    public function fetch(?CarbonInterface $since = null): iterable
    {
        $this->fetchedSince[] = $since;

        foreach ($this->items as $item) {
            if ($since !== null && CarbonImmutable::parse($item['posted'])->lt($since)) {
                return;
            }

            yield $item;
        }
    }

    public function normalise(mixed $raw): NormalisedJob
    {
        if (! is_array($raw) || ($raw['title'] ?? '') === '') {
            throw SkipItem::because('item has no title');
        }

        $id = (string) $raw['id'];

        return new NormalisedJob(
            source: $this->key(),
            sourceJobId: $id,
            sourceUrl: "https://fake.example/jobs/{$id}",
            applyUrl: "https://fake.example/jobs/{$id}/apply",
            title: (string) $raw['title'],
            company: (string) $raw['company'],
            description: null,
            tags: ['php'],
            workMode: WorkMode::Remote,
            eligibility: Eligibility::worldwide(),
            officeLocation: null,
            timezoneMin: null,
            timezoneMax: null,
            salary: null,
            contractType: ContractType::Unknown,
            seniority: Seniority::Unknown,
            postedAt: CarbonImmutable::parse($raw['posted']),
            fetchedAt: CarbonImmutable::now(),
            expiresAt: null,
            attribution: $this->attribution(),
        );
    }

    public function attribution(): string
    {
        return 'via Fake';
    }

    public function minInterval(): CarbonInterval
    {
        return CarbonInterval::hours(1);
    }
}
