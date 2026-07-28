<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when the regular expression engine gives up while reading the input.
 *
 * Such a failure is about the limits of the engine (the backtracking, the
 * recursion, the encoding of the subject) rather than about the input matching
 * nothing.
 */
final class PcreErrorException extends LexerRuntimeException
{
    public static function becausePcreErrorOccurs(
        ReadableInterface $source,
        TokenInterface $token,
        string $error,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('A PCRE error (%s) occurred while reading %s', $error, $token);

        return new self($source, $token, $message, previous: $previous);
    }
}
