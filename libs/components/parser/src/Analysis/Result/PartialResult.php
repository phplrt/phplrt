<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

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
     * @param TValue $value
     */
    public function __construct(
        mixed $value,
        /**
         * The first token the grammar says nothing about, which is where the
         * fragment ends.
         */
        public TokenInterface $token,
        /**
         * What the analysis has to say about the source: the very error the
         * source would be rejected with, ready to be thrown as it is.
         *
         * The reading stops where it can no longer go on, so there is exactly
         * one thing to say and this is it.
         */
        public RuntimeExceptionInterface $error,
    ) {
        parent::__construct($value);
    }
}
