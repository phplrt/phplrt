<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Parser\Analysis\Diagnostic;

/**
 * What an analysis has made of a source.
 *
 * @template-covariant TValue of mixed = mixed
 */
abstract readonly class Result
{
    /**
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        /**
         * Everything the analysis has to say about the source, in the order it
         * occurs there.
         *
         * @var list<Diagnostic>
         */
        public array $diagnostics = [],
    ) {}
}
