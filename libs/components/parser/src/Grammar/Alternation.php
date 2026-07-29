<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * Recognizes the first of the rules it refers to that matches the input.
 *
 * The rules are tried in the order they are written, and the first one that
 * matches wins: the rest are not tried at all, no matter how much more of the
 * input they would have read. A rule that has not matched leaves nothing
 * behind. The input is rewound before the next one is tried.
 *
 * This is what keeps the reading unambiguous: a source is always read in
 * exactly one way, and the order of the alternatives is a part of what the
 * grammar means rather than a matter of taste.
 *
 * For example, a rule reading either a number or a name, where #1 recognizes
 * a number and #2 recognizes a name:
 * ```php
 * new Alternation([1, 2]);
 * ```
 *
 * The very same rule written in EBNF:
 * ```ebnf
 * rule = number | name ;
 * ```
 *
 * and in pp2:
 * ```pp2
 * Rule : Number() | Name() ;
 * ```
 *
 * Note: EBNF says nothing about which alternative wins when several of them
 *       match, so a grammar written for it may well count on the longest one.
 *       The choice here is the ordered one of a PEG, where "a | ab" never
 *       reads "ab": the first alternative has already won by the time the
 *       second one would have been tried.
 *
 * The rule reads everything any of its alternatives reads, and the order only
 * decides which of them gets to do it:
 *
 * ```math
 * L(A \mid B) = L(A) \cup L(B)
 * ```
 */
final readonly class Alternation implements ProductionInterface
{
    public function __construct(
        /**
         * @var non-empty-list<int>
         */
        public array $ruleIds,
    ) {}
}
