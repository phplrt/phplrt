<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Engine;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Internal\Engine\Result\EngineResult;

/**
 * Reads a source against a grammar and builds what the grammar describes.
 *
 * Every engine reads the same sources into the same values and reports the
 * same failures, so which of them reads a source is decided by what is
 * cheaper for that source rather than by what the result has to be. An
 * engine that hands each source over to another one is an engine like any
 * other.
 *
 * Nothing about the source is an error: how far the grammar has gone is told
 * by the class of the result, and what stood in the way by the failure it
 * carries. An error is described by the failure alone, so an engine renders
 * none.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
interface EngineInterface
{
    /**
     * @param int<0, max> $initial the identifier of the rule the reading
     *        starts at
     * @throws LexerExceptionInterface in case of the source cannot be read
     *         into tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    public function read(
        ReadableInterface $source,
        int $initial,
        Building $building,
    ): EngineResult;
}
