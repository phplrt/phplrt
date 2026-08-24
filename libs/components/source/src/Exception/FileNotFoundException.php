<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * A file is absent from the file system.
 */
final class FileNotFoundException extends NotReadableException
{
    /**
     * @psalm-taint-sink file $pathname
     * @param non-empty-string $pathname
     */
    public static function becauseFileNotFound(string $pathname, ?\Throwable $prev = null): self
    {
        $message = 'File "%s" not found';

        return new self(\sprintf($message, $pathname), previous: $prev);
    }
}
