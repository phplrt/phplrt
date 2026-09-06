<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * The grammar has read nothing of the source.
 *
 * @template-extends Result<never>
 *
 * @readonly
 */
final class FailureResult extends Result
{
    public function __construct(
        /**
         * The token the grammar has stopped on.
         */
        public readonly TokenInterface $token,
        /**
         * What the analysis has to say about the source: the very error the
         * source would be rejected with, ready to be thrown as it is.
         *
         * The reading stops where it can no longer go on, so there is exactly
         * one thing to say and this is it.
         */
        public readonly RuntimeExceptionInterface $error,
    ) {
        parent::__construct();
    }
}
