<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

final readonly class Repetition implements RuleInterface
{
    /**
     * @param int<0, max> $gte
     * @param int<0, max>|float $lte
     */
    public function __construct(
        /**
         * @var int
         */
        public int $ruleId,
        /**
         * @var int<0, max>
         */
        public int $gte = 0,
        /**
         * @var int<0, max>|float
         */
        public int|float $lte = \INF,
    ) {
        \assert($lte >= $gte, 'Min repetitions count must be greater or equal than max repetitions');
    }
}
