<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface as ExceptionContract;
use Phplrt\Contracts\Lexer\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Renderer;

class UnrecognizedTokenException extends ParserRuntimeException
{
    public static function fromToken(ReadableInterface $src, TokenInterface $tok, ?\Throwable $prev = null): self
    {
        $message = \sprintf('Syntax error, unrecognized %s', self::getTokenValue($tok));

        return new static($message, $src, $tok, $prev);
    }

    public static function fromRuntimeException(ExceptionContract $e): self
    {
        $token = $e->getToken();
        $source = $e->getSource();

        return static::fromToken($source, $token, $e);
    }

    public static function fromLexerRuntimeException(LexerRuntimeExceptionInterface $e): self
    {
        $token = $e->getToken();
        $source = $e->getSource();

        return static::fromToken($source, $token, $e);
    }

    protected static function getTokenValue(TokenInterface $token): string
    {
        return (new Renderer())->render($token);
    }
}
