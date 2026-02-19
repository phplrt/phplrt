<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

final class OutOfRangeException extends BufferException
{
    public static function becausePositionOutOfRange(int $expected, int $size, ?\Throwable $previous = null): self
    {
        $message = 'Cannot rollback to offset %d, which is outside the buffer range [0..%d]';

        return new self(\sprintf($message, $expected, $size), 0, $previous);
    }
}
