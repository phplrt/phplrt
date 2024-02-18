<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Provides a lexer that allows you to analyze source tokens
 * starting from a specified offset.
 */
interface PositionalLexerInterface extends LexerInterface
{
    /**
     *  Returns a set of token objects from the passed source.
     *
     * @psalm-immutable This method may not be pure, but it does not change
     *                   the internal state of the lexer and can be used in
     *                   asynchronous and parallel computing.
     *
     * @param int<0, max> $offset Offset, starting from which you should
     *         start analyzing the source.
     *
     * @return iterable<array-key, TokenInterface> List of analyzed tokens.
     *
     * @throws LexerExceptionInterface An error occurs before source processing
     *          starts, when the given source cannot be recognized or if the
     *          lexer settings contain errors.
     * @throws LexerRuntimeExceptionInterface An exception that occurs after
     *          starting the lexical analysis and indicates problems in the
     *          analyzed source.
     */
    public function lex(ReadableInterface $source, int $offset = 0): iterable;
}
