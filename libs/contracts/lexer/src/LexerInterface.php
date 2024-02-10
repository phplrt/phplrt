<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;

/**
 * An interface that is an abstract implementation of a lexer.
 */
interface LexerInterface
{
    /**
     * Returns a set of token objects from the passed source.
     *
     * @param mixed $source
     * @return iterable<TokenInterface>
     *
     * @throws RuntimeExceptionInterface
     */
    public function lex($source): iterable;
}
