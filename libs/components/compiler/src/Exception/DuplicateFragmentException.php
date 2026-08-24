<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Node\Declaration\FragmentDeclaration;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a grammar declares two pieces of an expression under the very
 * same name.
 */
final class DuplicateFragmentException extends UnsupportedSyntaxException
{
    public static function becauseFragmentIsDeclaredTwice(
        ReadableInterface $source,
        FragmentDeclaration $declaration,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('The "%s" fragment has already been declared', $declaration->name);

        return new self(
            source: $source,
            offset: $declaration->offset,
            message: $message,
            length: $declaration->length,
            previous: $previous,
        );
    }
}
