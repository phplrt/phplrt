<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Engine\Result;

/**
 * What an engine has made of a source.
 *
 * How far the grammar has gone is told by the class of the result: the source
 * has been read to its end, the grammar has stopped inside it, or it has read
 * nothing at all.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @phpstan-sealed SuccessfulEngineResult|PartialEngineResult|FailureEngineResult
 *
 * @readonly
 */
abstract class EngineResult
{
    public function __construct(
        /**
         * What has been read has been built into, or {@see null} in case of
         * nothing has been built.
         */
        public readonly mixed $value = null,
    ) {}
}
