<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * The file the generated code is saved into cannot be written.
 */
final class OutputFileException extends GeneratorException
{
    /**
     * @param non-empty-string $pathname
     */
    public static function becauseFileIsNotWritten(string $pathname, ?\Throwable $previous = null): self
    {
        $message = \sprintf('The file "%s" cannot be written', $pathname);

        return new self($message, previous: $previous);
    }
}
