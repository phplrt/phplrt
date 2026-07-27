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

    /**
     * @psalm-taint-sink file $filename
     * @param non-empty-string $filename
     */
    public static function becauseFileNotReadable(string $filename, ?\Throwable $prev = null): self
    {
        $message = 'An error occurred while trying read the file "%s" (permission denied?)';

        return new self(\sprintf($message, $filename), self::CODE_FILE_READING, $prev);
    }
}
