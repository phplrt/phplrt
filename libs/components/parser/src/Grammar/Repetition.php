<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

final readonly class Repetition implements SequenceInterface
{
    public function __construct(
        public int $ruleId,
        /**
         * @var int<0, max>
         */
        public int $min = 0,
        /**
         * @var int<0, max>|float
         */
        public int|float $max = \INF,
    ) {
        \assert($max >= $min, 'Max repetitions count must be greater or equal than min repetitions');
        \assert(\is_int($max) || \is_infinite($max), 'Max repetitions may contain only integer or INF (float) values');
    }
}
