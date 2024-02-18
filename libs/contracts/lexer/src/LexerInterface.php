<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An interface that is an abstract implementation of a lexer.
 */
interface LexerInterface
{
    /**
     * Returns a set of token objects from the passed source.
     *
     * @psalm-immutable This method may not be pure, but it does not change
     *                  the internal state of the lexer and can be used in
     *                  asynchronous and parallel computing.
     *
     * @return iterable<array-key, TokenInterface> List of analyzed tokens.
     *
     * @throws LexerExceptionInterface An error occurs before source processing
     *         starts, when the given source cannot be recognized or if the
     *         lexer settings contain errors.
     * @throws LexerRuntimeExceptionInterface An exception that occurs after
     *         starting the lexical analysis and indicates problems in the
     *         analyzed source.
     */
    public function lex(ReadableInterface $source): iterable;
}
