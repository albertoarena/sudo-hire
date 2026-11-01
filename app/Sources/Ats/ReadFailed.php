<?php

namespace App\Sources\Ats;

use App\Sources\RunErrorKind;
use RuntimeException;
use Throwable;

/**
 * Thrown when the jobs of one company cannot be read. The reader stores the
 * message and the error kind for that company and goes on with the others.
 * The cause is the previous exception.
 */
final class ReadFailed extends RuntimeException
{
    public static function because(string $reason, ?Throwable $cause = null): self
    {
        return new self($reason, 0, $cause);
    }

    /**
     * The kind of the cause. With no cause, the kind is "other".
     */
    public function kind(): RunErrorKind
    {
        $cause = $this->getPrevious();

        return $cause === null ? RunErrorKind::Other : RunErrorKind::of($cause);
    }
}
