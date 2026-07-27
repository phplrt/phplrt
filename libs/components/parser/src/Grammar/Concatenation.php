<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * Recognizes the rules it refers to, one after another.
 *
 * Every one of them must be recognized, in the order they are written, and
 * each starts where the previous one has stopped. As soon as one of them is
 * not recognized, neither is the whole sequence: the input is rewound to where
 * the sequence has been entered, and everything read along the way is dropped.
 *
 * For example, a rule reading "1 + 2", where #1 recognizes a number and #2
 * recognizes a plus:
 * ```php
 * new Concatenation([1, 2, 1]);
 * ```
 *
 * The very same rule written in EBNF:
 * ```ebnf
 * rule = number , plus , number ;
 * ```
 *
 * and in pp2:
 * ```pp2
 * Rule : Number() Plus() Number() ;
 * ```
 *
 * A sequence reads everything the first rule reads followed by everything the
 * second one does:
 *
 * ```math
 * L(A\,B) = \{\, uv \mid u \in L(A),\ v \in L(B) \,\}
 * ```
 */
final readonly class Concatenation implements SequenceInterface
{
    public function __construct(
        /**
         * @var non-empty-list<int>
         */
        public array $ruleIds,
    ) {}
}
