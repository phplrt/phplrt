<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Generator\ClassModifier;

/**
 * The parser carries a modifier while it is named by nothing.
 */
final class UnsupportedClassModifierException extends GeneratorException
{
    public static function becauseModifierRequiresClassName(
        ClassModifier $modifier,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('An anonymous parser cannot be declared as %s', $modifier->value);

        return new self($message, previous: $previous);
    }
}
