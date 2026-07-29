<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

use Phplrt\Compiler\Node\Node;

/**
 * States how many times a statement may repeat.
 */
final readonly class Quantifier extends Node
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The least number of times the statement must repeat.
         *
         * @var int<0, max>
         */
        public int $min = 0,
        /**
         * The greatest number of times the statement may repeat, or
         * {@see \INF} in case of the number is not limited.
         *
         * The range is written by hand, so the greatest number may well be
         * lower than the least one.
         *
         * @var int<0, max>|float
         */
        public int|float $max = \INF,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
