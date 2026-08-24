<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * A file is there, but there is no read access to it.
 */
final class FileNotReadableException extends NotReadableException
{
    /**
     * @psalm-taint-sink file $filename
     * @param non-empty-string $filename
     */
    public static function becauseFileNotReadable(string $filename, ?\Throwable $prev = null): self
    {
        $message = 'An error occurred while trying read the file "%s"';

        return new self(\sprintf($message, $filename), previous: $prev);
    }
}
