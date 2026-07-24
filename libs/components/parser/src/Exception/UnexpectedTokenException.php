<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

class UnexpectedTokenException extends ParserException implements
    RuntimeExceptionInterface
{
    public function __construct(
        public readonly TokenInterface $token,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromToken(TokenInterface $token): self
    {
        return new self($token, \sprintf(
            'Syntax error, unexpected %s',
            self::describe($token),
        ));
    }

    private static function describe(TokenInterface $token): string
    {
        if ($token->value === '') {
            return $token->name ?? 'end of input';
        }

        return \sprintf('"%s"', $token->value);
    }
}
