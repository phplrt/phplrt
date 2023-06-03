<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An interface that implements methods for parsing source code.
 */
interface ParserInterface
{
    /**
     * Parses sources into an abstract source tree (AST) or list of AST nodes.
     *
     * @param string|resource|ReadableInterface $source
     * @return iterable<NodeInterface>
     *
     * @throws RuntimeExceptionInterface
     */
    public function parse($source): iterable;
}
