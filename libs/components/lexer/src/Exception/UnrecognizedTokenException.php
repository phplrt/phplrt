<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when the lexer is unable to recognize the input at a given offset.
 *
 * Unlike a silently truncated token stream, this guarantees that a successful
 * analysis has always consumed the source in full.
 */
final class UnrecognizedTokenException extends LexerRuntimeException
{
    public static function becauseInputIsUnrecognized(ReadableInterface $source, TokenInterface $token): self
    {
        return new self($source, $token, \sprintf(
            'Unrecognized %s at offset %d',
            $token,
            $token->offset,
        ));
    }

    public static function becausePcreErrorOccurs(
        ReadableInterface $source,
        TokenInterface $token,
        string $error,
    ): self {
        return new self($source, $token, \sprintf(
            'A PCRE error (%s) occurred while reading %s at offset %d',
            $error,
            $token,
            $token->offset,
        ));
    }
}
