<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Analysis;

use Phplrt\Parser\Builder\Definition\Reducer\ReducerInterface;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Describes the grammar the analysis passes complement with the metadata.
 *
 * Rule identifiers are already assigned here, so the grammar may no longer be
 * rewritten.
 */
final class ParserResultContext
{
    public function __construct(
        /**
         * @var list<RuleInterface>
         */
        public readonly array $grammar,
        /**
         * The identifier of the rule the analysis starts at.
         *
         * @var int<0, max>
         */
        public readonly int $initial,
        /**
         * The reducers converting the rules into the nodes, indexed by the
         * rule identifiers.
         *
         * @var array<int<0, max>, ReducerInterface>
         */
        public readonly array $reducers = [],
        /**
         * A map of rule name and its ID.
         *
         * @var array<non-empty-string, int>
         */
        public readonly array $constants = [],
        /**
         * The identifiers of the tokens a rule may begin with, indexed by the
         * rule identifiers, or {@see null} for a rule that may begin with any
         * token at all.
         *
         * @var array<int, array<int, true>|null>
         */
        public array $lookahead = [],
        /**
         * The rules that are kept in the resulting tree, indexed by the rule
         * identifiers.
         *
         * @var array<int, bool>
         */
        public array $kept = [],
        /**
         * The alternatives of every alternation worth trying, indexed by the
         * token the reading is at and then by the rule identifiers.
         *
         * @var array<int, array<int, list<int>>>
         */
        public array $choicePrediction = [],
    ) {}
}
