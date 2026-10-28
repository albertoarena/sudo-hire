<?php

namespace App\Schema;

use InvalidArgumentException;

/**
 * Thrown when data does not fit the job schema.
 */
final class InvalidJob extends InvalidArgumentException {}
