<?php

namespace App\Scan;

use App\Currency\CurrencyConverter;
use App\Currency\DisplayCurrency;
use App\Filters\FilterResult;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;

/**
 * The salary of a match for the terminal: the original text, the amount in the
 * display currency, and the "OTE" and "?" flags. The list takes the converted
 * amount alone. Pure: no I/O.
 */
final class SalaryText
{
    public static function of(?Salary $salary, FilterResult $result, CurrencyConverter $converter, DisplayCurrency $currency): string
    {
        $converted = self::amount($salary, $converter, $currency);
        $text = $salary === null ? 'not stated' : $salary->raw.($converted === null ? '' : " ({$converted})");

        if ($result->ote) {
            $text .= ' [OTE]';
        }

        if ($result->salaryUnknown) {
            $text .= ' [?]';
        }

        return $text;
    }

    /**
     * The converted salary for the list, e.g. "≈ EUR 81,000-99,000 a year". Null when the
     * currency is the same, the currency has no rate, or there is no salary.
     */
    public static function converted(?Salary $salary, CurrencyConverter $converter, DisplayCurrency $currency): ?string
    {
        $amount = self::amount($salary, $converter, $currency);

        return $amount === null ? null : "≈ {$amount}";
    }

    /**
     * "EUR 81,000-99,000 a year", or null when the currency is the same or has no rate.
     */
    private static function amount(?Salary $salary, CurrencyConverter $converter, DisplayCurrency $currency): ?string
    {
        if ($salary === null || $salary->currency === $currency->value) {
            return null;
        }

        $amounts = [];

        foreach ([$salary->min, $salary->max] as $amount) {
            if ($amount === null) {
                continue;
            }

            $converted = $converter->convert($amount, $salary->currency, $currency->value);

            if ($converted === null) {
                return null;
            }

            $amounts[] = number_format($converted);
        }

        $period = match ($salary->period) {
            SalaryPeriod::Year => ' a year',
            SalaryPeriod::Month => ' a month',
            SalaryPeriod::Day => ' a day',
            SalaryPeriod::Hour => ' an hour',
            default => '',
        };

        return $amounts === [] ? null : "{$currency->value} ".implode('-', $amounts).$period;
    }
}
