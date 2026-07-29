<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * The parser is asked to be declared under a name no class can be named by.
 */
final class InvalidClassNameException extends GeneratorException
{
    public static function becauseClassNameIsInvalid(string $class, ?\Throwable $previous = null): self
    {
        $message = \sprintf('The parser cannot be declared as "%s", which is not a valid class name', $class);

        return new self($message, previous: $previous);
    }
}
