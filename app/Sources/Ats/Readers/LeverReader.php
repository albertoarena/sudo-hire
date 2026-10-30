<?php

namespace App\Sources\Ats\Readers;

use App\Parsers\Salary\SalaryParser;
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
use App\Sources\SkipItem;
use App\Sources\Support\HtmlToText;
use App\Sources\Support\TitleFallback;
use Carbon\CarbonImmutable;

/**
 * Lever postings API. See the field mapping in docs/DECISIONS.md.
 */
final class LeverReader implements AtsReader
{
    use ReadsRawJobs;

    private const PERIODS = [
        'per-year-salary' => SalaryPeriod::Year,
        'per-month-salary' => SalaryPeriod::Month,
        'per-day-wage' => SalaryPeriod::Day,
        'per-hour-wage' => SalaryPeriod::Hour,
    ];

    public function ats(): Ats
    {
        return Ats::Lever;
    }

    public function read(CompanyEntry $company): array
    {
        return $this->jobList($this->getJson('https://api.lever.co/v0/postings/'.rawurlencode($company->slug).'?mode=json'));
    }

    public function normalise(AtsItem $item): NormalisedJob
    {
        $raw = $item->data;
        $categories = is_array($raw['categories'] ?? null) ? $raw['categories'] : [];
        $hostedUrl = $this->required($raw['hostedUrl'] ?? null, 'link');
        $title = $this->required($raw['text'] ?? null, 'title');
        $workMode = match (strtolower($this->text($raw['workplaceType'] ?? null) ?? '')) {
            'remote' => WorkMode::Remote,
            'hybrid' => WorkMode::Hybrid,
            'onsite', 'on-site' => WorkMode::Onsite,
            default => WorkMode::Unknown,
        };

        return $this->job($item, $this->required($raw['id'] ?? null, 'id'), $item->company->displayName, [
            'sourceUrl' => $hostedUrl,
            'applyUrl' => $this->text($raw['applyUrl'] ?? null) ?? $hostedUrl,
            'title' => $title,
            'description' => $this->description($raw),
            'tags' => $this->texts([$categories['team'] ?? null, $categories['department'] ?? null]),
            'workMode' => $workMode,
            'eligibility' => $this->eligibility($workMode, $raw['country'] ?? null),
            'officeLocation' => in_array($workMode, [WorkMode::Hybrid, WorkMode::Onsite], true) ? $this->officeLocation($categories) : null,
            'timezoneMin' => null,
            'timezoneMax' => null,
            'salary' => $this->salary($raw['salaryRange'] ?? null) ?? $this->salaryText($this->text($raw['additionalPlain'] ?? null)),
            'contractType' => TitleFallback::contractType(match (strtolower($this->text($categories['commitment'] ?? null) ?? '')) {
                'permanent', 'full-time', 'full time' => ContractType::FullTime,
                'part-time', 'part time' => ContractType::PartTime,
                'contract' => ContractType::Contract,
                default => ContractType::Unknown,
            }, $title),
            'seniority' => TitleFallback::seniority(Seniority::Unknown, $title),
            'postedAt' => $this->createdAt($raw['createdAt'] ?? null),
            'expiresAt' => null,
        ]);
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function description(array $raw): ?string
    {
        $parts = [$this->text($raw['descriptionPlain'] ?? null) ?? HtmlToText::convert($this->text($raw['description'] ?? null))];

        foreach (is_array($raw['lists'] ?? null) ? $raw['lists'] : [] as $list) {
            if (is_array($list)) {
                $content = HtmlToText::convert($this->text($list['content'] ?? null));
                $parts[] = trim(($this->text($list['text'] ?? null) ?? '')."\n".($content ?? ''));
            }
        }

        $parts[] = $this->text($raw['additionalPlain'] ?? null) ?? HtmlToText::convert($this->text($raw['additional'] ?? null));

        $text = implode("\n\n", array_filter($parts, fn (?string $part) => $part !== null && $part !== ''));

        return $text === '' ? null : $text;
    }

    /**
     * Lever gives the country as ISO alpha-2. Only a remote job is open to
     * the whole country. The text may say otherwise: the parser (M4) checks it.
     */
    private function eligibility(WorkMode $workMode, mixed $country): Eligibility
    {
        $country = strtoupper($this->text($country) ?? '');

        if ($workMode !== WorkMode::Remote || preg_match('/^[A-Z]{2}$/', $country) !== 1) {
            return Eligibility::unknown();
        }

        return Eligibility::restricted(countries: [$country]);
    }

    /**
     * @param  array<mixed>  $categories
     */
    private function officeLocation(array $categories): ?string
    {
        $all = is_array($categories['allLocations'] ?? null) ? $this->texts($categories['allLocations']) : [];

        return $all !== [] ? implode('; ', $all) : $this->text($categories['location'] ?? null);
    }

    private function salary(mixed $range): ?Salary
    {
        if (! is_array($range)) {
            return null;
        }

        $min = is_numeric($range['min'] ?? null) ? (float) $range['min'] : null;
        $max = is_numeric($range['max'] ?? null) ? (float) $range['max'] : null;
        $currency = strtoupper($this->text($range['currency'] ?? null) ?? '');
        $interval = $this->text($range['interval'] ?? null) ?? '';

        if (($min === null && $max === null) || $currency === '') {
            return null;
        }

        $amount = implode(' - ', $this->texts([$range['min'] ?? null, $range['max'] ?? null]));

        return new Salary($min, $max, $currency, self::PERIODS[$interval] ?? SalaryPeriod::Unknown, trim("{$amount} {$currency} {$interval}"));
    }

    /**
     * The first sentence of the additional text with a salary. The raw text is
     * that sentence, not the whole text.
     */
    private function salaryText(?string $text): ?Salary
    {
        foreach (preg_split('/(?<=[.!?])\s+|\n+/u', $text ?? '') ?: [] as $sentence) {
            if (($salary = (new SalaryParser)->parse(trim($sentence))) !== null) {
                return $salary;
            }
        }

        return null;
    }

    /**
     * @throws SkipItem
     */
    private function createdAt(mixed $milliseconds): CarbonImmutable
    {
        if (! is_int($milliseconds) && ! is_float($milliseconds)) {
            throw SkipItem::because('The job has no created date.');
        }

        return CarbonImmutable::createFromTimestampMsUTC($milliseconds);
    }
}
