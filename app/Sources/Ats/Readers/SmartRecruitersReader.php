<?php

namespace App\Sources\Ats\Readers;

use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use App\Sources\Ats\Ats;
use App\Sources\Ats\AtsItem;
use App\Sources\Ats\AtsReader;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Support\TitleFallback;

/**
 * SmartRecruiters posting API. See the field mapping in docs/DECISIONS.md.
 */
final class SmartRecruitersReader implements AtsReader
{
    use ReadsRawJobs;

    private const PAGE_SIZE = 100;

    private const MAX_PAGES = 10;

    public function ats(): Ats
    {
        return Ats::SmartRecruiters;
    }

    /**
     * The list has 100 postings for each page. It has no description:
     * that needs one more request for each posting, so it stays null.
     */
    public function read(CompanyEntry $company): array
    {
        $jobs = [];

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $offset = $page * self::PAGE_SIZE;
            $data = $this->getJson('https://api.smartrecruiters.com/v1/companies/'.rawurlencode($company->slug)."/postings?offset={$offset}&limit=".self::PAGE_SIZE);
            $postings = $this->jobList($data['content'] ?? null);
            $jobs = [...$jobs, ...$postings];
            $total = is_int($data['totalFound'] ?? null) ? $data['totalFound'] : 0;

            if ($postings === [] || $offset + self::PAGE_SIZE >= $total) {
                break;
            }
        }

        return $jobs;
    }

    public function normalise(AtsItem $item): NormalisedJob
    {
        $raw = $item->data;
        $id = $this->required($raw['id'] ?? null, 'id');
        $title = $this->required($raw['name'] ?? null, 'title');
        $company = is_array($raw['company'] ?? null) ? $raw['company'] : [];
        $location = is_array($raw['location'] ?? null) ? $raw['location'] : [];
        $url = 'https://jobs.smartrecruiters.com/'.rawurlencode($this->text($company['identifier'] ?? null) ?? $item->company->slug).'/'.rawurlencode($id);
        $workMode = match (true) {
            ($location['remote'] ?? null) === true => WorkMode::Remote,
            ($location['hybrid'] ?? null) === true => WorkMode::Hybrid,
            ($location['remote'] ?? null) === false && ($location['hybrid'] ?? null) === false => WorkMode::Onsite,
            default => WorkMode::Unknown,
        };

        return $this->job($item, $id, $this->text($company['name'] ?? null) ?? $item->company->displayName, [
            'sourceUrl' => $url,
            'applyUrl' => $url,
            'title' => $title,
            'description' => null,
            'tags' => $this->texts([$this->label($raw['department'] ?? null), $this->label($raw['function'] ?? null)]),
            'workMode' => $workMode,
            'eligibility' => $workMode === WorkMode::Remote ? $this->eligibility($location) : Eligibility::unknown(),
            'officeLocation' => $workMode === WorkMode::Remote ? null : $this->officeLocation($location),
            'timezoneMin' => null,
            'timezoneMax' => null,
            'salary' => null,
            'contractType' => TitleFallback::contractType(match ($this->id($raw['typeOfEmployment'] ?? null)) {
                'permanent' => ContractType::FullTime,
                'part-time' => ContractType::PartTime,
                'contract' => ContractType::Contract,
                default => ContractType::Unknown,
            }, $title),
            'seniority' => TitleFallback::seniority(match ($this->id($raw['experienceLevel'] ?? null)) {
                'entry_level', 'internship' => Seniority::Junior,
                default => Seniority::Unknown,
            }, $title),
            'postedAt' => $this->date($raw['releasedDate'] ?? null, 'release date'),
            'expiresAt' => null,
        ]);
    }

    /**
     * A remote job is open to its country, given as ISO alpha-2.
     *
     * @param  array<mixed>  $location
     */
    private function eligibility(array $location): Eligibility
    {
        $country = strtoupper($this->text($location['country'] ?? null) ?? '');

        return preg_match('/^[A-Z]{2}$/', $country) === 1 ? Eligibility::restricted(countries: [$country]) : Eligibility::unknown();
    }

    /**
     * @param  array<mixed>  $location
     */
    private function officeLocation(array $location): ?string
    {
        $parts = $this->texts([$location['city'] ?? null, $location['region'] ?? null, strtoupper($this->text($location['country'] ?? null) ?? '')]);

        return $this->text($location['fullLocation'] ?? null) ?? ($parts === [] ? null : implode(', ', $parts));
    }

    private function label(mixed $field): mixed
    {
        return is_array($field) ? ($field['label'] ?? null) : null;
    }

    private function id(mixed $field): ?string
    {
        return is_array($field) ? $this->text($field['id'] ?? null) : null;
    }
}
