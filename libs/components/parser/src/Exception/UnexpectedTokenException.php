<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnexpectedTokenException extends ParserRuntimeException
{
    public static function fromToken(
        ReadableInterface $source,
        TokenInterface $token,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('Syntax error, unexpected %s', $token);

        return new self(
            source: $source,
            token: $token,
            message: $message,
            previous: $previous,
        );
    }
}
