<?php

namespace App\Filters;

use App\Currency\CurrencyConverter;
use App\Currency\SalaryValues;
use App\Parsers\Salary\Ote;
use App\Schema\ContractType;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Settings\Settings;
use App\Settings\UnknownSalaryMode;

/**
 * Min annual salary for employee jobs, min day rate for contract jobs.
 * See REQUIREMENTS section 7. Pure: no I/O.
 */
final class SalaryFilter
{
    /** An amount with no period counts as yearly from this value, in the original currency. */
    private const float YEARLY_FROM = 10000;

    public static function check(NormalisedJob $job, Settings $settings, CurrencyConverter $converter): SalaryCheck
    {
        $salary = $job->salary;
        $ote = $salary !== null && Ote::in($salary->raw);
        $contract = self::isContract($job);
        $minimum = $contract ? $settings->minDayRate : $settings->minAnnualSalary;

        if ($minimum === null) {
            return new SalaryCheck(true, false, $ote);
        }

        $value = $salary === null ? null : self::value($salary, $contract, $settings, $converter);

        if ($value === null) {
            $lenient = $settings->unknownSalary === UnknownSalaryMode::Lenient;

            return new SalaryCheck($lenient, $lenient, $ote);
        }

        return new SalaryCheck($value >= $minimum, false, $ote);
    }

    /**
     * An unknown contract type with an hourly or daily rate counts as a contract.
     */
    private static function isContract(NormalisedJob $job): bool
    {
        return $job->contractType === ContractType::Contract
            || ($job->contractType === ContractType::Unknown
                && in_array($job->salary?->period, [SalaryPeriod::Hour, SalaryPeriod::Day], true));
    }

    /**
     * The annual salary or the day rate in the display currency. Null when it is not comparable.
     */
    private static function value(Salary $salary, bool $contract, Settings $settings, CurrencyConverter $converter): ?float
    {
        $amount = SalaryValues::compared($salary);

        $value = $contract
            ? match ($salary->period) {
                SalaryPeriod::Day => $amount,
                SalaryPeriod::Hour => SalaryValues::dayRate($amount, $settings->hoursPerDay),
                default => null,
            }
        : match ($salary->period) {
            SalaryPeriod::Year => $amount,
            SalaryPeriod::Month => SalaryValues::annual($amount, $settings->monthlyPayments),
            SalaryPeriod::Unknown => $amount >= self::YEARLY_FROM ? $amount : null,
            default => null,
        };

        return $value === null ? null : $converter->convert($value, $salary->currency, $settings->displayCurrency->value);
    }
}
