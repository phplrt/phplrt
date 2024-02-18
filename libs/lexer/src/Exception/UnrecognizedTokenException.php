<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Renderer;
use Phplrt\Contracts\Lexer\TokenInterface;

class UnrecognizedTokenException extends LexerRuntimeException
{
    public static function fromToken(ReadableInterface $src, TokenInterface $tok, \Throwable $prev = null): self
    {
        $message = \vsprintf('Syntax error, unrecognized %s', [
            (new Renderer())->render($tok),
        ]);

        return new static($message, $src, $tok, $prev);
    }
}
