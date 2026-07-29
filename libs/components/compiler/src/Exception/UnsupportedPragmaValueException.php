<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Node\Declaration\PragmaDeclaration;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a setting the compiler knows is given a value it cannot be given.
 */
final class UnsupportedPragmaValueException extends UnsupportedSyntaxException
{
    public static function becauseFlagIsNotSupported(
        ReadableInterface $source,
        PragmaDeclaration $declaration,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf(
            'Unrecognized PCRE modifier "%s" given to the "%%pragma %s"',
            $declaration->value,
            $declaration->name,
        );

        return self::create($source, $declaration, $message, $previous);
    }

    public static function becauseClassIsNotFound(
        ReadableInterface $source,
        PragmaDeclaration $declaration,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf(
            'The class "%s" given to the "%%pragma %s" does not exist',
            $declaration->value,
            $declaration->name,
        );

        return self::create($source, $declaration, $message, $previous);
    }

    /**
     * @param class-string $expected
     */
    public static function becauseClassIsNotAPass(
        ReadableInterface $source,
        PragmaDeclaration $declaration,
        string $expected,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf(
            'The class "%s" given to the "%%pragma %s" does not implement %s',
            $declaration->value,
            $declaration->name,
            $expected,
        );

        return self::create($source, $declaration, $message, $previous);
    }

    public static function becauseClassIsNotConstructable(
        ReadableInterface $source,
        PragmaDeclaration $declaration,
        \Throwable $previous,
    ): self {
        $message = \sprintf(
            'The class "%s" given to the "%%pragma %s" cannot be created: %s',
            $declaration->value,
            $declaration->name,
            $previous->getMessage(),
        );

        return self::create($source, $declaration, $message, $previous);
    }

    private static function create(
        ReadableInterface $source,
        PragmaDeclaration $declaration,
        string $message,
        ?\Throwable $previous,
    ): self {
        return new self(
            source: $source,
            offset: $declaration->offset,
            message: $message,
            end: $declaration->offset + $declaration->length,
            previous: $previous,
        );
    }
}
