<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Converts a source into the tokens it consists of.
 */
interface LexerInterface
{
    /**
     * Performs lexical analysis of the given source and returns its tokens.
     *
     * An implementation MUST NOT change its own state during the analysis, so
     * that the same lexer can be used in asynchronous and parallel computing.
     *
     * @param int<0, max> $offset the offset in bytes from the beginning of the
     *        source the analysis starts at
     * @return iterable<array-key, TokenInterface> the analyzed tokens
     * @throws LexerExceptionInterface if the given source cannot be recognized
     *         or the lexer settings contain errors
     * @throws RuntimeExceptionInterface if the analyzed source contains errors
     */
    public function lex(ReadableInterface $source, int $offset = 0): iterable;
}
