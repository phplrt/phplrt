<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;

/**
 * An interface that implements methods for parsing source code.
 */
interface ParserInterface
{
    /**
     * Parses sources into an abstract source tree (AST) or list of AST nodes.
     *
     * @return iterable<array-key, object>
     *
     * @throws RuntimeExceptionInterface
     */
    public function parse($source): iterable;
}
