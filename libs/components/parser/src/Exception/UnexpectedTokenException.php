<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Printer\PrettyTokenPrinter;

class UnexpectedTokenException extends ParserRuntimeException
{
    public static function fromToken(ReadableInterface $source, TokenInterface $token): self
    {
        return new self($source, $token, \sprintf(
            'Syntax error, unexpected %s',
            self::describe($token),
        ));
    }

    private static function describe(TokenInterface $token): string
    {
        if (\class_exists(PrettyTokenPrinter::class)) {
            return new PrettyTokenPrinter()
                ->print($token);
        }

        if ($token->value === '') {
            return $token->name ?? 'end of input';
        }

        return \sprintf('"%s"', $token->value);
    }
}
