<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Parser\Exception\ParserRuntimeExceptionInterface;

/**
 * An interface that implements methods for parsing source code.
 */
interface ParserInterface
{
    /**
     * Parses sources into an abstract source tree (AST) or list of AST nodes.
     *
     * @param string|resource|ReadableInterface $source
     * @return iterable|array|NodeInterface[]|NodeInterface
     *
     * @throws ParserRuntimeExceptionInterface
     */
    public function parse($source): iterable;
}
