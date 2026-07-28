<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

use Phplrt\Compiler\Node\Node;

/**
 * States how many times a statement may repeat.
 *
 * For example,
 * ```
 * ?       // {0,1}
 * *       // {0,}
 * +       // {1,}
 * {2,5}
 * ```
 */
final readonly class Quantifier extends Node
{
    /**
     * @param int<0, max> $min
     * @param int<0, max>|float $max
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The least number of times the statement must repeat.
         */
        public int $min = 0,
        /**
         * The greatest number of times the statement may repeat, or
         * {@see \INF} in case of the number is not limited.
         *
         * The range is written by hand, so the greatest number may well be
         * lower than the least one.
         */
        public int|float $max = \INF,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
