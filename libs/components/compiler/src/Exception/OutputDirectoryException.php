<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * The directory the generated code is saved into cannot be reached.
 */
final class OutputDirectoryException extends GeneratorException
{
    /**
     * @param non-empty-string $directory
     */
    public static function becauseDirectoryIsNotCreated(string $directory, ?\Throwable $previous = null): self
    {
        $message = \sprintf('The directory "%s" cannot be created', $directory);

        return new self($message, previous: $previous);
    }
}
