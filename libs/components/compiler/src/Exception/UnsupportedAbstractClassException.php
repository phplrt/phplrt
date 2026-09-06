<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * The parser is asked to be abstract while it is named by nothing.
 */
final class UnsupportedAbstractClassException extends GeneratorException
{
    public static function becauseAbstractClassIsNotNamed(?\Throwable $previous = null): self
    {
        $message = 'An abstract parser cannot be anonymous and must be declared under a name of its own';

        return new self($message, previous: $previous);
    }
}
