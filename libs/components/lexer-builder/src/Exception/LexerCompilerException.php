<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Exception;

use Phplrt\Contracts\Lexer\LexerInterface;

class LexerCompilerException extends \Exception
{
    public static function becauseInternalErrorOccurs(\Throwable $exception): self
    {
        $template = 'An internal error occurs while compiling the lexer: %s';

        return new self(\sprintf($template, $exception->getMessage()), 0, $exception);
    }

    /**
     * @param non-empty-string $state
     */
    public static function becauseEmbeddedLexerIsMalformed(string $state, \ParseError $error): self
    {
        $template = 'The lexer of the state "%s" cannot be compiled: %s';

        return new self(\sprintf($template, $state, $error->getMessage()), 0, $error);
    }

    /**
     * @param non-empty-string $state
     */
    public static function becauseEmbeddedLexerIsInvalid(string $state, string $type): self
    {
        $template = 'The lexer of the state "%s" must be an instance of %s, %s given';

        return new self(\sprintf($template, $state, LexerInterface::class, $type));
    }
}
