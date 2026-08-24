<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * A stream does not support offset (seek/rewind) changes, so a source that
 * begins somewhere other than at the head of it cannot be built.
 */
final class StreamNotSeekableException extends LogicException
{
    /**
     * @param non-empty-string $stream
     */
    public static function becauseStreamIsNotSeekable(string $stream, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" does not support offset (seek/rewind) changes';

        return new self(\sprintf($message, $stream), previous: $prev);
    }
}
