<?php

declare(strict_types=1);

namespace Phplrt\Position\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * An argument that a position cannot be built from.
 */
class InvalidArgumentException extends \InvalidArgumentException implements SourceExceptionInterface
{
    final public const int CODE_LINE_OUT_OF_RANGE = 0x01;
    final public const int CODE_COLUMN_OUT_OF_RANGE = 0x02;
    final public const int CODE_NON_POSITIVE_CHUNK_SIZE = 0x03;

    public static function becauseLineIsOutOfRange(int $line, ?\Throwable $prev = null): self
    {
        $message = 'Line number must be greater than or equal to 1, but %d given';

        return new self(\sprintf($message, $line), self::CODE_LINE_OUT_OF_RANGE, $prev);
    }

    public static function becauseColumnIsOutOfRange(int $column, ?\Throwable $prev = null): self
    {
        $message = 'Column number must be greater than or equal to 1, but %d given';

        return new self(\sprintf($message, $column), self::CODE_COLUMN_OUT_OF_RANGE, $prev);
    }

    public static function becauseChunkSizeIsNotPositive(int $bytes, ?\Throwable $prev = null): self
    {
        $message = 'Number of bytes read at once must be greater than 0, but %d given';

        return new self(\sprintf($message, $bytes), self::CODE_NON_POSITIVE_CHUNK_SIZE, $prev);
    }
}
