<?php

namespace App\Schema;

enum WorkMode: string
{
    case Remote = 'remote';
    case Hybrid = 'hybrid';
    case Onsite = 'onsite';
    case Unknown = 'unknown';
}
