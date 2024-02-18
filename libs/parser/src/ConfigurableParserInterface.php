<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Parser\ParserExceptionInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Parser\ParserRuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * @template TNode of object
 *
 * @template-extends ParserInterface<TNode>
 */
interface ConfigurableParserInterface extends ParserInterface
{
    /**
     * Parses sources into an abstract source tree (AST) or list of AST nodes.
     *
     * @param array<non-empty-string, mixed> $options List of additional
     *        runtime options for the parser (parsing context).
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
    public function parse(ReadableInterface $source, array $options = []): iterable;
}
