<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * A stream is not open for reading.
 */
final class StreamNotReadableException extends NotReadableException
{
    /**
     * @param non-empty-string $stream
     */
    public static function becauseStreamIsNotReadable(string $stream, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" is not open for reading';

        return new self(\sprintf($message, $stream), previous: $prev);
    }
}
