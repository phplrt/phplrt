<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes the given statement as many times as the quantifier allows.
 */
final readonly class Repetition extends Statement
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        public Statement $statement,
        public Quantifier $quantifier,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            children: [$statement, $quantifier],
            offset: $offset,
            length: $length,
        );
    }
}
