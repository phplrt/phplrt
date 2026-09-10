<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing;

use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Everything the recognition needs to know about a grammar, calculated once.
 *
 * The rules are kept exactly as they were passed, but anything that can be
 * figured out about them up front is precomputed here, so the tracer just reads
 * ready-made tables instead of asking the rules the same questions over and
 * over again.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @phpstan-type LookaheadTableType array<int, array<int, true>|null>
 * @phpstan-type KeptTableType array<int, bool>
 * @phpstan-type ChoicePredictionTableType array<int, array<int, list<int>>>
 * @phpstan-type MessageTableType array<int, non-empty-string>
 * @phpstan-type SequenceTableType array<int, list<int>>
 *
 * @readonly
 */
final class GrammarTable
{
    /**
     * The tokens each rule can start with, or "null" if the rule accepts
     * anything at all.
     *
     * There are two quite different reasons a rule accepts anything: either it
     * matches empty input, so whatever comes next is fine, or we simply weren't
     * given a lookahead table for this grammar. Both mean "never reject this
     * rule up front", so both are written down as "null" and the tracer doesn't
     * have to tell them apart. Which of the two it is, is decided long before
     * the recognition, so it is never asked here.
     *
     * @var LookaheadTableType
     */
    public readonly array $lookahead;

    /**
     * The rules that become a node of the result.
     *
     * A rule that is kept builds its own value out of its children and hands
     * it to its reducer. A rule that is dropped passes the children straight up
     * to the rule above and leaves nothing behind.
     *
     * @var KeptTableType
     */
    public readonly array $kept;

    /**
     * @param LookaheadTableType $lookahead
     * @param KeptTableType $kept
     */
    public function __construct(
        /**
         * @var list<RuleInterface>
         */
        public readonly array $rules,
        array $lookahead = [],
        array $kept = [],
        /**
         * The alternatives of every alternation worth trying, indexed by the
         * token the reading is at.
         *
         * An alternative is rejected by that token before it reads anything, so
         * which of them stand a chance is a question about the token rather
         * than about the input, and the compiler answers it once for all of
         * them. An alternation the grammar says nothing about is recognized by
         * trying every alternative it has, the way a regular PEG does.
         *
         * An alternative that is a terminal reading the very token the row is
         * chosen by is written down negated ("-id - 1"): the token is read in
         * place, without entering the alternative.
         *
         * ```php
         * [
         *     // alternation #7 is worth entering by its 2nd alternative alone
         *     // in case the reading is at token #3
         *     7 => [3 => [9], 4 => [9, 12]],
         * ]
         * ```
         *
         * @var ChoicePredictionTableType
         */
        public readonly array $choicePrediction = [],
        /**
         * The message describing the failure of a rule, indexed by the rule
         * identifiers.
         *
         * @var MessageTableType
         */
        public readonly array $messages = [],
        /**
         * The elements of every sequence that may leave one of them out,
         * indexed by the rule identifiers.
         *
         * An element that may be left out is written as the rule it wraps,
         * negated ("-id - 1"), so the sequence reads that rule in place and
         * goes on whether it has been read or not. A sequence missing from
         * the table is read exactly as it is declared.
         *
         * ```php
         * [
         *     // sequence #3 reads rule #7, then rule #9 in case it is there
         *     3 => [7, -10],
         * ]
         * ```
         *
         * @var SequenceTableType
         */
        public readonly array $sequences = [],
    ) {
        // A grammar that has not been described is recognized all the same: it
        // reads exactly the same sources, only slower, and errors get reported
        // at later stages (in more nested rules), since the rules alone don't
        // say where the reading was supposed to go.
        $this->lookahead = $lookahead === []
            ? \array_fill_keys(\array_keys($rules), null)
            : $lookahead;

        // If a rule doesn't change anything, it can be skipped. This reduces
        // the amount of tracing and speeds up subsequent processing, although
        // the computational result will remain the same.
        //
        // If the set of rules isn't explicitly passed, then we simply fill them
        // all in, assuming every rule is important.
        $this->kept = $kept === []
            ? \array_fill_keys(\array_keys($rules), true)
            : $kept;
    }
}
