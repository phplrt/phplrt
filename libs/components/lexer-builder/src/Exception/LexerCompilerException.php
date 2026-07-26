<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Exception;

class LexerCompilerException extends \Exception
{
    public static function becauseInternalErrorOccurs(\Throwable $exception): self
    {
        $template = 'An internal error occurs while compiling the lexer: %s';

        return new self(\sprintf($template, $exception->getMessage()), 0, $exception);
    }
}
