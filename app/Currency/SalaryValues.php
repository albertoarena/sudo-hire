<?php

namespace App\Currency;

use App\Schema\Salary;
use InvalidArgumentException;

/**
 * The values the salary filters compare (REQUIREMENTS section 7). Pure: no I/O.
 */
final class SalaryValues
{
    /**
     * Annual value of a monthly amount, for employee jobs.
     *
     * @param  int  $payments  monthly payments in a year: 12, 13 or 14
     */
    public static function annual(float $monthly, int $payments = 12): float
    {
        if (! in_array($payments, [12, 13, 14], true)) {
            throw new InvalidArgumentException("Monthly payments must be 12, 13 or 14, got {$payments}.");
        }

        return $monthly * $payments;
    }

    /**
     * Day rate of an hourly amount, for contract jobs.
     */
    public static function dayRate(float $hourly, float $hoursPerDay = 8): float
    {
        return $hourly * $hoursPerDay;
    }

    /**
     * The top of the range: max, or min if there is no max.
     */
    public static function compared(Salary $salary): float
    {
        return (float) ($salary->max ?? $salary->min);
    }
}
