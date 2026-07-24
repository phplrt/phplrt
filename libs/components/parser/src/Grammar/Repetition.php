<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

final readonly class Repetition implements ProductionInterface
{
    public function __construct(
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
        \assert(\is_float($lte) && !\is_infinite($lte), 'Min repetitions may contain only integer or INF (float) values');
    }
}
