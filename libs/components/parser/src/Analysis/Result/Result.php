<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

/**
 * What an analysis has made of a source.
 *
 * @template-covariant TValue of mixed = mixed
 */
abstract readonly class Result
{
    public function __construct(
        /**
         * What the source has been built into, or {@see null} in case of the
         * analysis has built nothing.
         *
         * @var TValue
         */
        public mixed $value = null,
    ) {}
}
