<?php

namespace App\Sources\Ats;

use RuntimeException;

/**
 * Thrown when the jobs of one company cannot be read. The reader stores the
 * message for that company and goes on with the others.
 */
final class ReadFailed extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
