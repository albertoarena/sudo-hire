<?php

namespace App\Sources\Ats;

use InvalidArgumentException;

/**
 * Thrown when a company entry is not "<ats>:<slug>" with a known ATS.
 */
final class InvalidCompanyEntry extends InvalidArgumentException {}
