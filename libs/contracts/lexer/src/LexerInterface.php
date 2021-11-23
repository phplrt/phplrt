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
use Psr\Http\Message\StreamInterface;

/**
 * An interface that is an abstract implementation of a lexer.
 *
 * @psalm-type SourceType = (ReadableInterface|StreamInterface|\SplFileInfo|string|resource)
 * @see StreamInterface
 * @see ReadableInterface
 */
interface LexerInterface
{
    /**
     * Returns a set of token objects from the passed source.
     *
     * @param mixed $source
     * @psalm-param SourceType $source
     * @return iterable<TokenInterface>
     * @throws LexerRuntimeExceptionInterface
     */
    public function lex(mixed $source): iterable;
}
