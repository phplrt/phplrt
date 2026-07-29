<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Looks ahead at the given statement without reading it.
 *
 * The statement is recognized the way any other one is, and whatever it has
 * read is given back afterwards: what the predicate decides is only whether the
 * rule it belongs to goes on.
 */
final readonly class Predicate extends Statement
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        public Statement $statement,
        /**
         * Contains {@see true} in case of the rule goes on when the statement
         * is recognized, or {@see false} in case of the rule goes on when it
         * is not.
         */
        public bool $isExpected = true,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            children: [$statement],
            offset: $offset,
            length: $length,
        );
    }
}
