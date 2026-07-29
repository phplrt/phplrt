<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a token definition matches an empty string.
 *
 * Such a definition can never advance the lexer position and would otherwise
 * cause an infinite loop, so it is always a lexer definition error.
 */
final class EmptyTokenException extends LexerRuntimeException
{
    public static function becauseTokenIsEmpty(
        ReadableInterface $source,
        TokenInterface $token,
        ?\Throwable $previous = null,
    ): self {
        $message = 'Token %s matches an empty string, which makes the lexer unable to advance';
        $message = \sprintf($message, $token);

        return new self($source, $token, $message, previous: $previous);
    }
}
