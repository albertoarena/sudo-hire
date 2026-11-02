<?php

namespace App\Scan;

use App\Currency\CurrencyConverter;
use App\Currency\DisplayCurrency;
use App\Filters\FilterResult;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;

/**
 * The salary of a match for the terminal: the original text, the amount in the
 * display currency, and the "OTE" and "?" flags. The list has its own format for
 * the converted amount (forList). Pure: no I/O.
 */
final class SalaryText
{
    /** @var array<string, string> display currency to its symbol */
    private const array SYMBOLS = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];

    public static function of(?Salary $salary, FilterResult $result, CurrencyConverter $converter, DisplayCurrency $currency): string
    {
        $text = $salary === null ? 'not stated' : $salary->raw.self::converted($salary, $converter, $currency);

        if ($result->ote) {
            $text .= ' [OTE]';
        }

        if ($result->salaryUnknown) {
            $text .= ' [?]';
        }

        return $text;
    }

    /**
     * The converted salary for the list, as DESIGN.md "Job row" shows it, e.g. "≈ €66k - €84k / year".
     * A monthly salary shows as a year, with the monthly payments of the settings, also in the
     * same currency. Null when there is no salary, the currency has no rate, or there is nothing
     * to add: the same currency and not monthly.
     */
    public static function forList(?Salary $salary, CurrencyConverter $converter, DisplayCurrency $currency, int $monthlyPayments = 12): ?string
    {
        $monthly = $salary?->period === SalaryPeriod::Month;

        if ($salary === null || ($salary->currency === $currency->value && ! $monthly)) {
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

            $amounts[] = self::SYMBOLS[$currency->value].self::short($monthly ? $converted * $monthlyPayments : $converted);
        }

        $period = match ($salary->period) {
            SalaryPeriod::Year, SalaryPeriod::Month => ' / year',
            SalaryPeriod::Day => ' / day',
            SalaryPeriod::Hour => ' / hour',
            default => '',
        };

        return $amounts === [] ? null : '≈ '.implode(' - ', $amounts).$period;
    }

    /**
     * "66k" from 10,000, "5.5k" from 1,000, else a whole number: "625".
     */
    private static function short(float $amount): string
    {
        if ($amount >= 10000) {
            return round($amount / 1000).'k';
        }

        if ($amount >= 1000) {
            return rtrim(rtrim(number_format($amount / 1000, 1, '.', ''), '0'), '.').'k';
        }

        return number_format($amount);
    }

    /**
     * " (EUR 81,000-99,000 a year)", or "" when the currency is the same or has no rate.
     */
    private static function converted(Salary $salary, CurrencyConverter $converter, DisplayCurrency $currency): string
    {
        if ($salary->currency === $currency->value) {
            return '';
        }

        $amounts = [];

        foreach ([$salary->min, $salary->max] as $amount) {
            if ($amount === null) {
                continue;
            }

            $converted = $converter->convert($amount, $salary->currency, $currency->value);

            if ($converted === null) {
                return '';
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

        return $amounts === [] ? '' : " ({$currency->value} ".implode('-', $amounts)."{$period})";
    }
}
