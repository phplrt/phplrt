<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Analysis\Diagnostic;

/**
 * The grammar has read a fragment of the source and stopped.
 *
 * The fragment is the longest one the grammar recognizes.
 *
 * @template-covariant TValue of mixed = mixed
 *
 * @template-extends Result<TValue>
 */
final readonly class PartialResult extends Result
{
    /**
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        /**
         * What the fragment has been built into, or {@see null} in case of the
         * analysis has built nothing.
         *
         * @var TValue
         */
        public mixed $value,
        /**
         * The first token the grammar says nothing about, which is where the
         * fragment ends.
         */
        public TokenInterface $token,
        array $diagnostics = [],
    ) {
        parent::__construct($diagnostics);
    }
}
