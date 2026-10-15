<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;

/**
 * Occurs when the source code cannot be split into the tokens the grammar is
 * written in terms of.
 */
final class LexicalErrorException extends ParserRuntimeException
{
    public static function becauseSourceIsNotTokenizable(LexerRuntimeExceptionInterface $e): self
    {
        return new self($e->source, $e->token, $e->getMessage(), previous: $e);
    }
}
