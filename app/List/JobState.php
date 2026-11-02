<?php

namespace App\List;

/**
 * The state of a job in the list. A job has one state at a time, or none (New).
 */
enum JobState: string
{
    case Saved = 'saved';
    case Applied = 'applied';
    case Hidden = 'hidden';
}
