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
     * @param non-empty-string $name
     */
    public static function becauseLexerIsNotDefined(string $name): self
    {
        $template = 'The lexer "%s" the reading is handed over to has not been defined';

        return new self(\sprintf($template, $name));
    }

    /**
     * @param non-empty-string $name
     */
    public static function becauseEmbeddedLexerIsMalformed(string $name, \ParseError $error): self
    {
        $template = 'The lexer "%s" cannot be compiled: %s';

        return new self(\sprintf($template, $name, $error->getMessage()), 0, $error);
    }

    /**
     * @param non-empty-string $name
     */
    public static function becauseEmbeddedLexerIsInvalid(string $name, string $type): self
    {
        $template = 'The lexer "%s" must be an instance of %s, %s given';

        return new self(\sprintf($template, $name, LexerInterface::class, $type));
    }
}
