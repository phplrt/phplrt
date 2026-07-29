<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Analysis\Diagnostic;

/**
 * The grammar has read nothing of the source.
 *
 * @template-extends Result<never>
 */
final readonly class FailureResult extends Result
{
    /**
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        /**
         * The token the grammar has stopped on.
         */
        public TokenInterface $token,
        array $diagnostics = [],
    ) {
        parent::__construct($diagnostics);
    }
}
