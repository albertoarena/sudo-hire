<?php

namespace App\Scan;

use App\Currency\CurrencyConverter;
use App\Currency\DisplayCurrency;
use App\Filters\FilterResult;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;

/**
 * The salary of a match for the terminal: the original text, the amount in the
 * display currency, and the "OTE" and "?" flags. Pure: no I/O.
 */
final class SalaryText
{
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
