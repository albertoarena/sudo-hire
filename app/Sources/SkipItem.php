<?php

namespace App\Sources;

use RuntimeException;

/**
 * Thrown by JobSource::normalise() for one item that cannot be normalised.
 * It never stops the run: the caller counts it and moves on.
 */
final class SkipItem extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
