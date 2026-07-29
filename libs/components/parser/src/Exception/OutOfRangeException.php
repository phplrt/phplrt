<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

final class OutOfRangeException extends BufferException
{
    public static function becausePositionOutOfRange(int $expected, int $lastIndex, ?\Throwable $previous = null): self
    {
        $message = 'Cannot rollback to offset %d, which is outside the buffer range [0..%d]';
        $message = \sprintf($message, $expected, $lastIndex);

        return new self($message, 0, $previous);
    }
}
