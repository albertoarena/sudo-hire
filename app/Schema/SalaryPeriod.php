<?php

namespace App\Schema;

enum SalaryPeriod: string
{
    case Year = 'year';
    case Month = 'month';
    case Day = 'day';
    case Hour = 'hour';
    case Unknown = 'unknown';
}
