<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * A token and a kept rule are named the same way, so the parser would declare
 * one constant twice.
 */
final class DuplicateConstantException extends GeneratorException
{
    public static function becauseNameIsTaken(string $name, ?\Throwable $previous = null): self
    {
        $message = 'The parser cannot declare the "%s" constant for both the token and the rule named so';
        $message = \sprintf($message, $name);

        return new self($message, previous: $previous);
    }
}
