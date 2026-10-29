<?php

namespace App\Schema;

enum Seniority: string
{
    case Junior = 'junior';
    case Mid = 'mid';
    case Senior = 'senior';
    case Lead = 'lead';
    case Unknown = 'unknown';
}
