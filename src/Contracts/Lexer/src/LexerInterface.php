<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Lexer\Exception\LexerRuntimeExceptionInterface;

/**
 * An interface that is an abstract implementation of a lexer.
 */
interface LexerInterface
{
    /**
     * Returns a set of token objects from the passed source.
     *
     * @param string|resource|ReadableInterface $source
     * @param int $offset
     * @return iterable|TokenInterface[]
     *
     * @throws LexerRuntimeExceptionInterface
     */
    public function lex($source, int $offset = 0): iterable;
}
