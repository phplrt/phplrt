<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Parser\Analysis\Diagnostic;

/**
 * The grammar has read the source in full.
 *
 * @template-covariant TValue of mixed = null
 *
 * @template-extends Result<TValue>
 */
readonly class SuccessfulResult extends Result
{
    /**
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        /**
         * What the source has been built into, or {@see null} in case of the
         * analysis has built nothing.
         *
         * @var TValue
         */
        public mixed $value = null,
        array $diagnostics = [],
    ) {
        parent::__construct($diagnostics);
    }
}
