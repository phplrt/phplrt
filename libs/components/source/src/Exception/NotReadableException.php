<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * An exception that occurs when there is no read access to the file,
 * such as "Access Denied".
 */
class NotReadableException extends NotAccessibleException
{
    final public const int CODE_FILE_READING = 0x01;
    final public const int CODE_STREAM_READING = 0x02;
    final public const int CODE_STREAM_REWINDING = 0x03;

    /**
     * @psalm-taint-sink file $filename
     * @param non-empty-string $filename
     */
    public static function becauseFileNotReadable(string $filename, ?\Throwable $prev = null): self
    {
        $message = 'An error occurred while trying read the file "%s"';

        return new self(\sprintf($message, $filename), self::CODE_FILE_READING, $prev);
    }

    /**
     * @param non-empty-string $stream
     */
    public static function becauseStreamIsNotReadable(string $stream, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" is not open for reading';

        return new self(\sprintf($message, $stream), self::CODE_STREAM_READING, $prev);
    }

    /**
     * @param non-empty-string $stream
     */
    public static function becauseStreamCannotBeRewound(string $stream, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" cannot be rewound, so everything located before '
            . 'the position it is at has already been given away';

        return new self(\sprintf($message, $stream), self::CODE_STREAM_REWINDING, $prev);
    }
}
