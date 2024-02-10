<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Provides a lexer that allows you to analyze source tokens
 * starting from a specified offset.
 */
interface PositionalLexerInterface extends LexerInterface
{
    /**
     * @param mixed $source
     * @param int<0, max> $offset Offset, starting from which you should
     *        start analyzing the source.
     *
     * @return iterable<TokenInterface>
     *
     * @throws RuntimeExceptionInterface
     */
    public function lex($source, int $offset = 0): iterable;
}
