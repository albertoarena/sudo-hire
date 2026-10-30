<?php

namespace App\Sources\Ats\Readers;

use App\Parsers\Location\LocationParser;
use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use App\Sources\Ats\Ats;
use App\Sources\Ats\AtsItem;
use App\Sources\Ats\AtsReader;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Support\TitleFallback;

/**
 * Ashby posting API. See the field mapping in docs/DECISIONS.md.
 */
final class AshbyReader implements AtsReader
{
    use ReadsRawJobs;

    private const PERIODS = [
        '1 YEAR' => SalaryPeriod::Year,
        '1 MONTH' => SalaryPeriod::Month,
        '1 DAY' => SalaryPeriod::Day,
        '1 HOUR' => SalaryPeriod::Hour,
    ];

    public function ats(): Ats
    {
        return Ats::Ashby;
    }

    public function read(CompanyEntry $company): array
    {
        $data = $this->getJson('https://api.ashbyhq.com/posting-api/job-board/'.rawurlencode($company->slug).'?includeCompensation=true');

        return $this->jobList($data['jobs'] ?? null);
    }

    public function normalise(AtsItem $item): NormalisedJob
    {
        $raw = $item->data;
        $jobUrl = $this->required($raw['jobUrl'] ?? null, 'link');
        $title = $this->required($raw['title'] ?? null, 'title');
        $workMode = match ($this->text($raw['workplaceType'] ?? null)) {
            'Remote' => WorkMode::Remote,
            'Hybrid' => WorkMode::Hybrid,
            'OnSite' => WorkMode::Onsite,
            default => ($raw['isRemote'] ?? false) === true ? WorkMode::Remote : WorkMode::Unknown,
        };

        return $this->job($item, $this->required($raw['id'] ?? null, 'id'), $item->company->displayName, [
            'sourceUrl' => $jobUrl,
            'applyUrl' => $this->text($raw['applyUrl'] ?? null) ?? $jobUrl,
            'title' => $title,
            'description' => $this->text($raw['descriptionPlain'] ?? null),
            'tags' => $this->texts([$raw['department'] ?? null, $raw['team'] ?? null]),
            'workMode' => $workMode,
            'eligibility' => $this->eligibility($workMode, $raw),
            'officeLocation' => in_array($workMode, [WorkMode::Hybrid, WorkMode::Onsite], true) ? $this->text($raw['location'] ?? null) : null,
            'timezoneMin' => null,
            'timezoneMax' => null,
            'salary' => $this->salary($raw['compensation'] ?? null),
            'contractType' => TitleFallback::contractType(match ($this->text($raw['employmentType'] ?? null)) {
                'FullTime' => ContractType::FullTime,
                'PartTime' => ContractType::PartTime,
                'Contract' => ContractType::Contract,
                default => ContractType::Unknown,
            }, $title),
            'seniority' => TitleFallback::seniority(Seniority::Unknown, $title),
            'postedAt' => $this->date($raw['publishedAt'] ?? null, 'publish date'),
            'expiresAt' => null,
        ]);
    }

    /**
     * The address country of the job and of each secondary location, e.g.
     * "European Union" (region eu) and "Spain". A secondary location named
     * after a city ("Berlin") still has its country in the address.
     * Hybrid and on-site jobs give unknown: their places are offices.
     *
     * @param  array<mixed>  $raw
     */
    private function eligibility(WorkMode $workMode, array $raw): Eligibility
    {
        if (in_array($workMode, [WorkMode::Hybrid, WorkMode::Onsite], true)) {
            return Eligibility::unknown();
        }

        $secondary = is_array($raw['secondaryLocations'] ?? null) ? $raw['secondaryLocations'] : [];
        $countries = $this->texts(array_map(fn (mixed $location) => $this->addressCountry($location), [$raw, ...$secondary]));

        return $countries === [] ? Eligibility::unknown() : (new LocationParser)->countryList($countries);
    }

    private function addressCountry(mixed $location): mixed
    {
        $address = is_array($location) ? ($location['address'] ?? null) : null;
        $postal = is_array($address) ? ($address['postalAddress'] ?? null) : null;

        return is_array($postal) ? ($postal['addressCountry'] ?? null) : null;
    }

    /**
     * The salary component of the summary. Its currency can differ from
     * the job's country: use it as it is.
     */
    private function salary(mixed $compensation): ?Salary
    {
        $components = is_array($compensation) && is_array($compensation['summaryComponents'] ?? null)
            ? $compensation['summaryComponents']
            : [];

        foreach ($components as $component) {
            if (! is_array($component) || ($component['compensationType'] ?? null) !== 'Salary') {
                continue;
            }

            $min = is_numeric($component['minValue'] ?? null) ? (float) $component['minValue'] : null;
            $max = is_numeric($component['maxValue'] ?? null) ? (float) $component['maxValue'] : null;
            $currency = $this->text($component['currencyCode'] ?? null);

            if (($min === null && $max === null) || $currency === null) {
                continue;
            }

            $raw = $this->text($compensation['scrapeableCompensationSalarySummary'] ?? null)
                ?? trim(implode(' - ', $this->texts([$component['minValue'] ?? null, $component['maxValue'] ?? null]))." {$currency}");

            return new Salary($min, $max, $currency, self::PERIODS[$this->text($component['interval'] ?? null) ?? ''] ?? SalaryPeriod::Unknown, $raw);
        }

        return null;
    }
}
