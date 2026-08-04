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
 */
final readonly class GrammarTable
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
     * @var array<int, array<int, true>|null>
     */
    public array $lookahead;

    /**
     * The rules that become a node of the result.
     *
     * A rule that is present builds its own value out of its children and hands
     * it to its reducer. A rule that is absent passes the children straight up
     * to the rule above and leaves nothing behind.
     *
     * @var array<int, bool>
     */
    public array $presentInTree;

    /**
     * @param list<RuleInterface> $rules
     * @param int<0, max> $initial
     * @param array<int, array<int, true>|null> $lookahead
     * @param array<int, bool> $presentInTree
     */
    public function __construct(
        /**
         * @var list<RuleInterface>
         */
        public array $rules,
        /**
         * The identifier of the rule the recognition starts at.
         *
         * @var int<0, max>
         */
        public int $initial,
        array $lookahead = [],
        array $presentInTree = [],
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
         * ```php
         * [
         *     // alternation #7 is worth entering by its 2nd alternative alone
         *     // in case the reading is at token #3
         *     7 => [3 => [9], 4 => [9, 12]],
         * ]
         * ```
         *
         * @var array<int, array<int, list<int>>>
         */
        public array $branchesByToken = [],
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
        $this->presentInTree = $presentInTree === []
            ? \array_fill_keys(\array_keys($rules), true)
            : $presentInTree;
    }
}
