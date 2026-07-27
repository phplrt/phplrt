<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * The exception that occurs in the absence of a file in the file system.
 */
class NotFoundException extends NotReadableException
{
    final public const int CODE_FILE_NOT_FOUND = 0x01;

    /**
     * @psalm-taint-sink file $pathname
     * @param non-empty-string $pathname
     */
    public static function becauseFileNotFound(string $pathname): self
    {
        $message = 'File "%s" not found';

        return new static(\sprintf($message, $pathname), self::CODE_FILE_NOT_FOUND);
    }
}
