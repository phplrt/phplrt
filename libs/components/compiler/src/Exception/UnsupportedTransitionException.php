<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Node\Declaration\TokenDeclaration;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a token switches between two states that are not nested in one
 * another, which cannot be expressed by a lexer reading a fragment.
 */
final class UnsupportedTransitionException extends UnsupportedSyntaxException
{
    /**
     * @param non-empty-string $from
     * @param non-empty-string $to
     */
    public static function becauseTransitionIsNotSupported(
        ReadableInterface $source,
        TokenDeclaration $declaration,
        string $from,
        string $to,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf(
            'A fragment read in the state "%s" cannot be continued by the state "%s": '
                . 'only entering a state and leaving it back can be expressed',
            $from,
            $to,
        );

        return new self(
            source: $source,
            offset: $declaration->offset,
            message: $message,
            end: $declaration->offset + $declaration->length,
            previous: $previous,
        );
    }
}
