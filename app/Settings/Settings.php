<?php

namespace App\Settings;

use App\Currency\DisplayCurrency;
use App\Schema\ContractType;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use BackedEnum;
use DateTimeZone;

/**
 * The user's settings. Pure: M6a stores them, the filters read them.
 * See docs/REQUIREMENTS.md section 8 for the values and the defaults.
 *
 * Amounts are in the display currency. Unknown work modes, contract types and
 * seniorities are not settings: the filters decide what to do with them.
 */
final readonly class Settings
{
    private const array POSTED_WITHIN_HOURS = [24, 72, 168, 336, 720];

    private const array MONTHLY_PAYMENTS = [12, 13, 14];

    /**
     * @param  string|null  $country  ISO 3166-1 alpha-2, null until the first run
     * @param  string  $timezone  IANA name, e.g. "Europe/Rome"
     * @param  list<WorkMode>  $workModes
     * @param  list<string>  $keywordsInclude
     * @param  list<string>  $keywordsExclude
     * @param  list<ContractType>  $contractTypes
     * @param  list<Seniority>  $seniorities
     * @param  list<string>  $companyBlocklist
     * @param  list<string>  $disabledSources  source names
     */
    public function __construct(
        public ?string $country,
        public string $timezone,
        public array $workModes,
        public UnknownEligibilityMode $unknownEligibility,
        public ?int $timezoneOverlapHours,
        public array $keywordsInclude,
        public array $keywordsExclude,
        public bool $matchDescription,
        public ?float $minAnnualSalary,
        public ?float $minDayRate,
        public UnknownSalaryMode $unknownSalary,
        public array $contractTypes,
        public array $seniorities,
        public int $postedWithinHours,
        public array $companyBlocklist,
        public array $disabledSources,
        public DisplayCurrency $displayCurrency,
        public int $monthlyPayments,
        public int $hoursPerDay,
        public int $notificationIntervalHours,
    ) {
        if ($country !== null && preg_match('/^[A-Z]{2}$/', $country) !== 1) {
            throw new InvalidSettings("Country must be ISO 3166-1 alpha-2, got \"{$country}\".");
        }

        if (! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidSettings("Unknown timezone \"{$timezone}\".");
        }

        self::requireKnown('work mode', $workModes, WorkMode::Unknown);
        self::requireKnown('contract type', $contractTypes, ContractType::Unknown);
        self::requireKnown('seniority', $seniorities, Seniority::Unknown);

        if ($timezoneOverlapHours !== null && ($timezoneOverlapHours < 0 || $timezoneOverlapHours > 12)) {
            throw new InvalidSettings('Time difference must be from 0 to 12 hours.');
        }

        if (($minAnnualSalary !== null && $minAnnualSalary <= 0) || ($minDayRate !== null && $minDayRate <= 0)) {
            throw new InvalidSettings('A minimum salary or day rate must be above 0. Leave the field empty to turn it off.');
        }

        if (! in_array($postedWithinHours, self::POSTED_WITHIN_HOURS, true)) {
            throw new InvalidSettings('Posted within must be 24, 72, 168, 336 or 720 hours.');
        }

        if (! in_array($monthlyPayments, self::MONTHLY_PAYMENTS, true)) {
            throw new InvalidSettings('Monthly payments must be 12, 13 or 14.');
        }

        if ($hoursPerDay < 1 || $hoursPerDay > 24) {
            throw new InvalidSettings('Hours per day must be from 1 to 24.');
        }

        if ($notificationIntervalHours < 1 || $notificationIntervalHours > 24) {
            throw new InvalidSettings('The notification interval must be from 1 to 24 hours.');
        }
    }

    public static function defaults(string $timezone): self
    {
        return new self(
            country: null,
            timezone: $timezone,
            workModes: [WorkMode::Remote],
            unknownEligibility: UnknownEligibilityMode::Group,
            timezoneOverlapHours: null,
            keywordsInclude: [],
            keywordsExclude: [],
            matchDescription: false,
            minAnnualSalary: null,
            minDayRate: null,
            unknownSalary: UnknownSalaryMode::Lenient,
            contractTypes: [ContractType::FullTime, ContractType::PartTime, ContractType::Contract],
            seniorities: [Seniority::Junior, Seniority::Mid, Seniority::Senior, Seniority::Lead],
            postedWithinHours: 72,
            companyBlocklist: [],
            disabledSources: [],
            displayCurrency: DisplayCurrency::Eur,
            monthlyPayments: 12,
            hoursPerDay: 8,
            notificationIntervalHours: 2,
        );
    }

    /**
     * Builds the settings from stored values. A missing key takes its default,
     * so stored settings keep working when a new setting is added.
     *
     * @param  array<string, mixed>  $data  the shape of toArray()
     */
    public static function fromArray(array $data): self
    {
        $timezone = $data['timezone'] ?? 'UTC';
        $data = array_merge(self::defaults(is_string($timezone) ? $timezone : 'UTC')->toArray(), $data);

        return new self(
            country: self::nullableString($data['country']),
            timezone: self::string($data['timezone']),
            workModes: self::enums(WorkMode::class, $data['workModes']),
            unknownEligibility: self::enum(UnknownEligibilityMode::class, $data['unknownEligibility']),
            timezoneOverlapHours: $data['timezoneOverlapHours'] === null ? null : self::int($data['timezoneOverlapHours']),
            keywordsInclude: self::strings($data['keywordsInclude']),
            keywordsExclude: self::strings($data['keywordsExclude']),
            matchDescription: (bool) $data['matchDescription'],
            minAnnualSalary: self::nullableFloat($data['minAnnualSalary']),
            minDayRate: self::nullableFloat($data['minDayRate']),
            unknownSalary: self::enum(UnknownSalaryMode::class, $data['unknownSalary']),
            contractTypes: self::enums(ContractType::class, $data['contractTypes']),
            seniorities: self::enums(Seniority::class, $data['seniorities']),
            postedWithinHours: self::int($data['postedWithinHours']),
            companyBlocklist: self::strings($data['companyBlocklist']),
            disabledSources: self::strings($data['disabledSources']),
            displayCurrency: self::enum(DisplayCurrency::class, $data['displayCurrency']),
            monthlyPayments: self::int($data['monthlyPayments']),
            hoursPerDay: self::int($data['hoursPerDay']),
            notificationIntervalHours: self::int($data['notificationIntervalHours']),
        );
    }

    /**
     * @return array<string, mixed> plain values, safe to store as JSON
     */
    public function toArray(): array
    {
        $values = fn (array $enums) => array_map(fn (BackedEnum $enum) => $enum->value, $enums);

        return [
            'country' => $this->country,
            'timezone' => $this->timezone,
            'workModes' => $values($this->workModes),
            'unknownEligibility' => $this->unknownEligibility->value,
            'timezoneOverlapHours' => $this->timezoneOverlapHours,
            'keywordsInclude' => $this->keywordsInclude,
            'keywordsExclude' => $this->keywordsExclude,
            'matchDescription' => $this->matchDescription,
            'minAnnualSalary' => $this->minAnnualSalary,
            'minDayRate' => $this->minDayRate,
            'unknownSalary' => $this->unknownSalary->value,
            'contractTypes' => $values($this->contractTypes),
            'seniorities' => $values($this->seniorities),
            'postedWithinHours' => $this->postedWithinHours,
            'companyBlocklist' => $this->companyBlocklist,
            'disabledSources' => $this->disabledSources,
            'displayCurrency' => $this->displayCurrency->value,
            'monthlyPayments' => $this->monthlyPayments,
            'hoursPerDay' => $this->hoursPerDay,
            'notificationIntervalHours' => $this->notificationIntervalHours,
        ];
    }

    /**
     * @param  list<BackedEnum>  $values
     */
    private static function requireKnown(string $name, array $values, BackedEnum $unknown): void
    {
        if ($values === []) {
            throw new InvalidSettings("Select at least one {$name}.");
        }

        if (in_array($unknown, $values, true)) {
            throw new InvalidSettings("\"unknown\" is not a {$name} setting.");
        }
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private static function enum(string $class, mixed $value): BackedEnum
    {
        return (is_string($value) ? $class::tryFrom($value) : null)
            ?? throw new InvalidSettings('Invalid value '.json_encode($value).' for '.$class.'.');
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $class
     * @return list<T>
     */
    private static function enums(string $class, mixed $values): array
    {
        if (! is_array($values)) {
            throw new InvalidSettings('Expected a list for '.$class.'.');
        }

        return array_values(array_map(fn (mixed $value) => self::enum($class, $value), $values));
    }

    /**
     * Trimmed, with no empty entries and no repeats (case-insensitive). The first spelling wins.
     *
     * @return list<string>
     */
    private static function strings(mixed $values): array
    {
        if (! is_array($values)) {
            throw new InvalidSettings('Expected a list of strings.');
        }

        $result = [];

        foreach ($values as $value) {
            $value = trim(self::string($value));
            $key = mb_strtolower($value);

            if ($value !== '' && ! isset($result[$key])) {
                $result[$key] = $value;
            }
        }

        return array_values($result);
    }

    private static function string(mixed $value): string
    {
        return is_string($value) ? $value : throw new InvalidSettings('Expected a string, got '.json_encode($value).'.');
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : self::string($value);
    }

    private static function int(mixed $value): int
    {
        return is_int($value) ? $value : throw new InvalidSettings('Expected a whole number, got '.json_encode($value).'.');
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return is_int($value) || is_float($value) ? (float) $value : throw new InvalidSettings('Expected a number, got '.json_encode($value).'.');
    }
}
