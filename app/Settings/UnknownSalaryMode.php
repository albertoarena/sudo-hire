<?php

namespace App\Settings;

/**
 * What the filter does with a job that has no salary. See REQUIREMENTS section 7.
 */
enum UnknownSalaryMode: string
{
    case Strict = 'strict';
    case Lenient = 'lenient';
}
