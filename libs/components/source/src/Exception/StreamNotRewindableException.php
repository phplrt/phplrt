<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * A stream cannot be moved back to a position it has already given away.
 */
final class StreamNotRewindableException extends NotReadableException
{
    /**
     * @param non-empty-string $stream
     */
    public static function becauseStreamCannotBeRewound(string $stream, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" cannot be rewound, so everything located before '
            . 'the position it is at has already been given away';

        return new self(\sprintf($message, $stream), previous: $prev);
    }
}
