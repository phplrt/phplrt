<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser;

use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Represents the result of building a parser.
 *
 * Rule identifiers are the positions of the rules in the grammar, so a rule ID
 * is always enough to reach its definition.
 *
 * @phpstan-import-type ReducerType from RuleDefinition
 */
final readonly class ParserBuilderResult
{
    public function __construct(
        /**
         * @var list<RuleInterface>
         */
        public array $grammar,
        /**
         * The identifier of the rule the analysis starts at.
         *
         * @var int<0, max>
         */
        public int $initial,
        /**
         * The identifiers of the tokens a rule may begin with, indexed by the
         * rule identifiers.
         *
         * @var array<int, array<int, true>>
         */
        public array $first,
        /**
         * The rules that may be recognized without consuming a token, indexed
         * by the rule identifiers.
         *
         * @var array<int, bool>
         */
        public array $nullable,
        /**
         * The rules that are present in the resulting tree, indexed by the rule
         * identifiers.
         *
         * @var array<int, bool>
         */
        public array $kept,
        /**
         * The callbacks converting the rules into the nodes, indexed by the
         * rule identifiers.
         *
         * @var array<int<0, max>, ReducerType>
         */
        public array $reducers = [],
        /**
         * A map of rule name and its ID.
         *
         * @var array<non-empty-string, int>
         */
        public array $constants = [],
    ) {}
}
