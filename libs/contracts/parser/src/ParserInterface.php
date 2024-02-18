<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An interface that implements methods for parsing source code.
 *
 * @template TNode of object
 */
interface ParserInterface
{
    /**
     * Parses sources into an abstract source tree (AST) or list of AST nodes.
     *
     * @return iterable<array-key, TNode>
     *
     * @throws ParserExceptionInterface An error occurs before source processing
     *         starts, when the given source cannot be recognized or if the
     *         parser settings contain errors.
     * @throws ParserRuntimeExceptionInterface An exception that occurs after
     *         starting the parsing and indicates problems in the analyzed
     *         source.
     */
    public function parse(ReadableInterface $source): iterable;
}
