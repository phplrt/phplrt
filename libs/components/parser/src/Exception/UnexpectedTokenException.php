<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnexpectedTokenException extends ParserRuntimeException
{
    /**
     * The number of the expectations an error message tells by name, the rest
     * of them being counted instead.
     *
     * @var int<1, max>
     */
    private const int MAX_LISTED_EXPECTATIONS = 3;

    /**
     * @param int<0, max>|null $length
     */
    public function __construct(
        ReadableInterface $source,
        TokenInterface $token,
        string $message,
        /**
         * The names of the tokens that could have been read instead of the one
         * the error occurred on.
         *
         * @var list<non-empty-string>
         */
        public readonly array $expected = [],
        ?int $length = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            source: $source,
            token: $token,
            message: $message,
            length: $length,
            code: $code,
            previous: $previous,
        );
    }

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
            expected: $expected,
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
            default => \sprintf(', one of %s expected', self::createExpectations($expected)),
        };
    }

    /**
     * @param non-empty-list<non-empty-string> $expected
     * @return non-empty-string
     */
    private static function createExpectations(array $expected): string
    {
        $hidden = \count($expected) - self::MAX_LISTED_EXPECTATIONS;

        if ($hidden <= 0) {
            return \implode(', ', $expected);
        }

        $expectations = \array_slice($expected, 0, self::MAX_LISTED_EXPECTATIONS);

        return \sprintf('%s (+%d more expectations)', \implode(', ', $expectations), $hidden);
    }
}
