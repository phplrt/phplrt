<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * An argument that a source cannot accept.
 */
class InvalidArgumentException extends \InvalidArgumentException implements SourceExceptionInterface
{
    final public const int CODE_NON_POSITIVE_BYTES_COUNT = 0x01;
    final public const int CODE_NEGATIVE_OFFSET = 0x02;
    final public const int CODE_OFFSET_OUT_OF_RANGE = 0x03;

    public static function becauseBytesCountIsNotPositive(int $bytes, ?\Throwable $prev = null): self
    {
        $message = 'Number of bytes to read must be greater than 0, but %d given';

        return new self(\sprintf($message, $bytes), self::CODE_NON_POSITIVE_BYTES_COUNT, $prev);
    }

    public static function becauseOffsetIsNegative(int $offset, ?\Throwable $prev = null): self
    {
        $message = 'Offset must be greater than or equal to 0, but %d given';

        return new self(\sprintf($message, $offset), self::CODE_NEGATIVE_OFFSET, $prev);
    }

    /**
     * @param int<0, max> $initial
     */
    public static function becauseOffsetIsOutOfRange(int $offset, int $initial, ?\Throwable $prev = null): self
    {
        $message = 'Offset must be less than or equal to %d for a source beginning '
            . 'at the position %d of the stream, but %d given';

        return new self(
            \sprintf($message, \PHP_INT_MAX - $initial, $initial, $offset),
            self::CODE_OFFSET_OUT_OF_RANGE,
            $prev,
        );
    }
}
