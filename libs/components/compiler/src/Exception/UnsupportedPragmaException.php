<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Node\Declaration\PragmaDeclaration;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a grammar configures a setting the compiler knows nothing about.
 */
final class UnsupportedPragmaException extends UnsupportedSyntaxException
{
    public static function becausePragmaIsNotSupported(
        ReadableInterface $source,
        PragmaDeclaration $declaration,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('Unrecognized pragma "%s"', $declaration->name);

        return new self(
            source: $source,
            offset: $declaration->offset,
            message: $message,
            length: $declaration->length,
            previous: $previous,
        );
    }
}
