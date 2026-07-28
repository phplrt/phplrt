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
    public static function becauseInputIsUnrecognized(
        ReadableInterface $source,
        TokenInterface $token,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('Unrecognized %s', $token);

        return new self($source, $token, $message, previous: $previous);
    }
}
