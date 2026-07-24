<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Occurs when a token definition matches an empty string.
 *
 * Such a definition can never advance the lexer position and would otherwise
 * cause an infinite loop, so it is always a lexer definition error.
 */
final class EmptyTokenException extends LexerRuntimeException
{
    public static function becauseTokenIsEmpty(TokenInterface $token): self
    {
        return new self($token, \sprintf(
            'Token %s matches an empty string at offset %d, which makes the lexer unable to advance',
            $token->name ?? '#' . $token->id,
            $token->offset,
        ));
    }
}
