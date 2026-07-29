<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnexpectedTokenException extends ParserRuntimeException
{
    /**
     * The identifiers of the tokens that could have been read instead, in no
     * particular order.
     *
     * @var list<int>
     */
    public private(set) array $expected = [];

    /**
     * @param list<int> $expected
     */
    public static function fromToken(
        ReadableInterface $source,
        TokenInterface $token,
        array $expected = [],
        ?\Throwable $previous = null,
    ): self {
        $instance = new self(
            source: $source,
            token: $token,
            message: \sprintf('Syntax error, unexpected %s', $token),
            previous: $previous,
        );

        $instance->expected = $expected;

        return $instance;
    }
}
