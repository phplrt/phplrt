<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Parser\Analysis\Diagnostic;

/**
 * The grammar has read the source in full.
 *
 * @template-covariant TValue of mixed = mixed
 *
 * @template-extends Result<TValue>
 */
final readonly class SuccessfulResult extends Result
{
    /**
     * @param TValue $value
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        /**
         * What the source has been built into, or {@see null} in case of the
         * analysis has built nothing.
         */
        public mixed $value = null,
        array $diagnostics = [],
    ) {
        parent::__construct($diagnostics);
    }
}
