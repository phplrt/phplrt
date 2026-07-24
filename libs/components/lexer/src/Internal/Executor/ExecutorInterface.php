<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal\Executor;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Reads a single lexer state.
 *
 * An executor knows nothing about the lexer states: it only reads as much as
 * it can (or must), leaving the decision on what to do next to the {@see Lexer}.
 *
 * @internal this is an internal library interface, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
interface ExecutorInterface
{
    /**
     * Appends every token it reads to the given list.
     *
     * Writing into the caller's list (instead of returning an own one) keeps
     * the tokens of all states in a single array, so no merging is needed.
     *
     * @param int<0, max> $offset
     * @param list<TokenInterface> $tokens
     *
     * @param-out list<TokenInterface> $tokens
     *
     * @return int<0, max> the offset the analysis has stopped at
     * @throws LexerExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    public function run(string $source, int $offset, array &$tokens): int;
}
