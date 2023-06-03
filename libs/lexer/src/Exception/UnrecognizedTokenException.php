<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Renderer;
use Phplrt\Contracts\Lexer\TokenInterface;

class UnrecognizedTokenException extends LexerRuntimeException
{
    /**
     * @var string
     */
    private const ERROR_UNRECOGNIZED_TOKEN = 'Syntax error, unrecognized %s';

    /**
     * @param ReadableInterface $src
     * @param TokenInterface $tok
     * @param \Throwable|null $prev
     * @return static
     */
    public static function fromToken(ReadableInterface $src, TokenInterface $tok, \Throwable $prev = null): self
    {
        $message = \vsprintf(self::ERROR_UNRECOGNIZED_TOKEN, [
            (new Renderer())->render($tok),
        ]);

        return new static($message, $src, $tok, $prev);
    }
}
