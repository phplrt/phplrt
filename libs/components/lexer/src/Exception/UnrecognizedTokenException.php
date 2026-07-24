<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Occurs when the lexer is unable to recognize the input at a given offset.
 *
 * Unlike a silently truncated token stream, this guarantees that a successful
 * analysis has always consumed the source in full.
 */
final class UnrecognizedTokenException extends LexerRuntimeException
{
    public static function becauseInputIsUnrecognized(TokenInterface $token): self
    {
        return new self($token, \sprintf(
            'Unrecognized %s at offset %d',
            $token,
            $token->offset,
        ));
    }

    public static function becausePcreErrorOccurs(TokenInterface $token, string $error): self
    {
        return new self($token, \sprintf(
            'A PCRE error (%s) occurred while reading %s at offset %d',
            $error,
            $token,
            $token->offset,
        ));
    }
}
