<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * The position a reading starts at is located before the beginning of the
 * source.
 */
final class NegativeOffsetException extends InvalidArgumentException
{
    public static function becauseOffsetIsNegative(int $offset, ?\Throwable $prev = null): self
    {
        $message = 'Offset must be greater than or equal to 0, but %d given';

        return new self(\sprintf($message, $offset), previous: $prev);
    }
}
