<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnexpectedTokenException extends ParserRuntimeException
{
    /**
     * @param list<non-empty-string> $expected
     */
    public static function becauseUnexpectedTokenProduced(
        ReadableInterface $source,
        TokenInterface $token,
        array $expected = [],
        ?\Throwable $previous = null,
    ): self {
        return new self(
            source: $source,
            token: $token,
            message: self::createMessage($token, $expected),
            previous: $previous,
        );
    }

    /**
     * @param list<non-empty-string> $expected
     * @return non-empty-string
     */
    private static function createMessage(TokenInterface $token, array $expected): string
    {
        $message = \sprintf('Syntax error, unexpected %s', $token);

        return $message . match (\count($expected)) {
            0 => '',
            1 => \sprintf(', %s expected', \implode(', ', $expected)),
            default => \sprintf(', one of %s expected', \implode(', ', $expected)),
        };
    }
}
