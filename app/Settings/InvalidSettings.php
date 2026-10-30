<?php

namespace App\Settings;

use InvalidArgumentException;

/**
 * Thrown when a setting has a value that REQUIREMENTS section 8 does not allow.
 */
final class InvalidSettings extends InvalidArgumentException {}
