<?php

namespace App\Sources\Ats;

/**
 * The applicant tracking systems that the ATS reader can read.
 */
enum Ats: string
{
    case Greenhouse = 'greenhouse';
    case Lever = 'lever';
    case Ashby = 'ashby';
    case Personio = 'personio';
}
