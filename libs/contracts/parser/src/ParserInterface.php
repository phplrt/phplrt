<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Converts a source into the result of its syntax analysis.
 *
 * @template TResult of mixed = mixed
 */
interface ParserInterface
{
    /**
     * Performs syntax analysis of the given source and returns its result.
     *
     * The shape of the result is defined by the implementation, which MAY
     * return any value the analyzed source is converted into, like an
     * abstract syntax tree (AST) or a list of its nodes.
     *
     * @return TResult the result of the analysis
     * @throws ParserExceptionInterface if the given source cannot be
     *         recognized or the parser settings contain errors
     * @throws RuntimeExceptionInterface if the analyzed source contains errors
     */
    public function parse(ReadableInterface $source): mixed;
}
