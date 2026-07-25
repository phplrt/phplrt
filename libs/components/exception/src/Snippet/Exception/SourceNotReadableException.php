<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet\Exception;

/**
 * Occurs when the source code cannot be read from the given file.
 */
final class SourceNotReadableException extends SnippetException
{
    public static function becauseFileIsNotReadable(string $pathname, ?\Throwable $previous = null): self
    {
        $message = 'Unable to read the source code from the "%s" file';

        return new self(\sprintf($message, $pathname), 0, $previous);
    }
}
