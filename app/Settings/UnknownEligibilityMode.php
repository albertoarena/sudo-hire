<?php

namespace App\Settings;

/**
 * What the filter does with a job whose eligibility is unknown. See REQUIREMENTS section 6.
 */
enum UnknownEligibilityMode: string
{
    case Group = 'group';
    case Strict = 'strict';
    case Lenient = 'lenient';
}
