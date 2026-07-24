<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * An interface that implements methods for parsing source code.
 *
 * @template TResult of object = object
 */
interface ParserInterface
{
    /**
     * Parses sources into an abstract source tree (AST) or list of AST nodes.
     *
     * @return TResult
     * @throws ParserExceptionInterface an error occurs before source processing
     *         starts, when the given source cannot be recognized or if the
     *         parser settings contain errors
     * @throws RuntimeExceptionInterface an exception that occurs after
     *         starting the parsing and indicates problems in the analyzed
     *         source
     */
    public function parse(string $source): mixed;
}
