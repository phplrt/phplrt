<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes the given statement as many times as the quantifier allows.
 *
 * For example,
 * ```
 * Number()*
 * ```
 */
final readonly class Repetition extends Statement
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public Statement $statement,
        public Quantifier $quantifier,
        int $offset = 0,
    ) {
        parent::__construct([$statement, $quantifier], $offset);
    }
}
