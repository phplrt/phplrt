<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * The stream a source reads from has been closed from the outside, so there
 * is nothing left for the source to give away.
 */
final class ClosedStreamException extends NotReadableException
{
    public static function becauseStreamIsClosed(?\Throwable $prev = null): self
    {
        $message = 'The stream has been closed from the outside';

        return new self($message, previous: $prev);
    }
}
