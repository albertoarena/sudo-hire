<?php

namespace App\Filters;

enum EligibilityOutcome
{
    case Pass;
    case Fail;

    /** Shown in the "Location not stated" group, not counted in the notification. */
    case Group;
}
