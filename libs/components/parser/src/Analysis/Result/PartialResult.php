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
 * @template-covariant TValue of mixed = null
 *
 * @template-extends SuccessfulResult<TValue>
 */
final readonly class PartialResult extends SuccessfulResult
{
    /**
     * @param list<Diagnostic> $diagnostics
     * @param TValue $value
     */
    public function __construct(
        /**
         * The first token the grammar says nothing about, which is where the
         * fragment ends.
         */
        public TokenInterface $token,
        mixed $value = null,
        array $diagnostics = [],
    ) {
        parent::__construct($value, $diagnostics);
    }
}
