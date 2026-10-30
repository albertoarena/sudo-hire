<?php

namespace App\Sources;

use InvalidArgumentException;

/**
 * A source key that the registry does not know.
 */
final class UnknownSource extends InvalidArgumentException
{
    public static function key(string $key): self
    {
        return new self("Unknown source \"{$key}\".");
    }
}
