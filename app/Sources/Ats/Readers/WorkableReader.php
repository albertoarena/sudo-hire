<?php

namespace App\Sources\Ats\Readers;

use App\Parsers\Location\LocationParser;
use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use App\Sources\Ats\Ats;
use App\Sources\Ats\AtsItem;
use App\Sources\Ats\AtsReader;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Support\HtmlToText;
use App\Sources\Support\TitleFallback;

/**
 * Workable public jobs API. See the field mapping in docs/DECISIONS.md.
 */
final class WorkableReader implements AtsReader
{
    use ReadsRawJobs;

    public function ats(): Ats
    {
        return Ats::Workable;
    }

    public function read(CompanyEntry $company): array
    {
        $data = $this->getJson('https://www.workable.com/api/accounts/'.rawurlencode($company->slug).'?details=true');

        return $this->jobList($data['jobs'] ?? null);
    }

    public function normalise(AtsItem $item): NormalisedJob
    {
        $raw = $item->data;
        $url = $this->required($raw['url'] ?? null, 'link');
        $title = $this->required($raw['title'] ?? null, 'title');
        $remote = ($raw['telecommuting'] ?? false) === true;

        return $this->job($item, $this->required($raw['shortcode'] ?? null, 'id'), $item->company->displayName, [
            'sourceUrl' => $url,
            'applyUrl' => $this->text($raw['application_url'] ?? null) ?? $url,
            'title' => $title,
            'description' => HtmlToText::convert($this->text($raw['description'] ?? null)),
            'tags' => $this->texts([$raw['department'] ?? null, $raw['function'] ?? null]),
            'workMode' => $remote ? WorkMode::Remote : WorkMode::Unknown,
            'eligibility' => $remote ? $this->eligibility($raw) : Eligibility::unknown(),
            'officeLocation' => $remote ? null : $this->officeLocation($raw),
            'timezoneMin' => null,
            'timezoneMax' => null,
            'salary' => null,
            'contractType' => TitleFallback::contractType(match (strtolower($this->text($raw['employment_type'] ?? null) ?? '')) {
                'full-time' => ContractType::FullTime,
                'part-time' => ContractType::PartTime,
                'contract' => ContractType::Contract,
                default => ContractType::Unknown,
            }, $title),
            'seniority' => TitleFallback::seniority(Seniority::Unknown, $title),
            'postedAt' => $this->date($raw['published_on'] ?? null, 'publish date'),
            'expiresAt' => null,
        ]);
    }

    /**
     * A remote job lists its countries as ISO alpha-2 codes. One country is the
     * country of the job. A list of several countries can be narrower than the
     * job text, so it gives unknown, and the title can narrow it.
     * With no list, the country name of the job is used.
     *
     * @param  array<mixed>  $raw
     */
    private function eligibility(array $raw): Eligibility
    {
        $locations = is_array($raw['locations'] ?? null) ? $raw['locations'] : [];
        $codes = array_map(
            fn (mixed $location) => strtoupper($this->text(is_array($location) ? ($location['countryCode'] ?? null) : null) ?? ''),
            $locations,
        );
        $codes = array_values(array_unique(array_filter($codes, fn (string $code) => preg_match('/^[A-Z]{2}$/', $code) === 1)));

        if (count($codes) === 1) {
            return Eligibility::restricted(countries: $codes);
        }

        if ($codes !== []) {
            return Eligibility::unknown();
        }

        return (new LocationParser)->countryList($this->texts([$raw['country'] ?? null]));
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function officeLocation(array $raw): ?string
    {
        $parts = $this->texts([$raw['city'] ?? null, $raw['state'] ?? null, $raw['country'] ?? null]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
