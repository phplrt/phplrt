<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Requires the token behind it and the token ahead of it to be written one
 * right after another.
 *
 * @readonly
 */
final class Adjacency extends Statement
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * Contains {@see true} in case of the rule goes on when the tokens are
         * written with nothing in between, or {@see false} in case of the rule
         * goes on when they are not.
         */
        public readonly bool $isExpected = true,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
