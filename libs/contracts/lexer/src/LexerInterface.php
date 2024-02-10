<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An interface that is an abstract implementation of a lexer.
 */
interface LexerInterface
{
    /**
     * Returns a set of token objects from the passed source.
     *
     * @param string|resource|ReadableInterface $source
     * @return iterable<TokenInterface>
     *
     * @throws RuntimeExceptionInterface
     */
    public function lex(mixed $source): iterable;
}
