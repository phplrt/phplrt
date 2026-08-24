<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * The number of bytes a reading takes out of a source is not above zero.
 */
final class NonPositiveBytesCountException extends InvalidArgumentException
{
    public static function becauseBytesCountIsNotPositive(int $bytes, ?\Throwable $prev = null): self
    {
        $message = 'Number of bytes to read must be greater than 0, but %d given';

        return new self(\sprintf($message, $bytes), previous: $prev);
    }
}
