<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * The code the compiled grammar is written down as cannot be produced.
 */
final class CodeGenerationException extends GeneratorException
{
    public static function becauseCodeIsNotGenerated(\Throwable $previous): self
    {
        $message = \sprintf('The parser cannot be written down: %s', $previous->getMessage());

        return new self($message, previous: $previous);
    }
}
