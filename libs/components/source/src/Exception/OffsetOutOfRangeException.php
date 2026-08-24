<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * The position a reading starts at is located beyond the last one a stream
 * holds.
 */
final class OffsetOutOfRangeException extends InvalidArgumentException
{
    /**
     * @param int<0, max> $initial
     */
    public static function becauseOffsetIsOutOfRange(int $offset, int $initial, ?\Throwable $prev = null): self
    {
        $message = 'Offset must be less than or equal to %d for a source beginning '
            . 'at the position %d of the stream, but %d given';

        return new self(
            \sprintf($message, \PHP_INT_MAX - $initial, $initial, $offset),
            previous: $prev,
        );
    }
}
