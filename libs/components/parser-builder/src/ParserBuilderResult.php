<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Parser\Builder\Definition\Reducer\ReducerInterface;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\Transformer\RuntimeParserTransformer;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Parser;

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
         * rule identifiers, or {@see null} for a rule that may begin with any
         * token at all.
         *
         * @var array<int, array<int, true>|null>
         */
        public array $lookahead,
        /**
         * The rules that are kept in the resulting tree, indexed by the rule
         * identifiers.
         *
         * @var array<int, bool>
         */
        public array $kept,
        /**
         * The reducers converting the rules into the nodes, indexed by the
         * rule identifiers.
         *
         * @var array<int<0, max>, ReducerInterface>
         */
        public array $reducers = [],
        /**
         * A map of rule name and its ID.
         *
         * @var array<non-empty-string, int>
         */
        public array $constants = [],
        /**
         * The alternatives of every alternation worth trying, indexed by the
         * token the reading is at and then by the rule identifiers.
         *
         * @var array<int, array<int, list<int>>>
         */
        public array $choicePrediction = [],
        /**
         * A map of token ID and the way an error has to name it: a name, or
         * what an anonymous token is recognized by.
         *
         * @var array<int, non-empty-string>
         */
        public array $expectations = [],
    ) {}

    /**
     * @throws ParserCompilerException in case of the grammar cannot be run
     */
    public function toParser(LexerInterface $lexer): Parser
    {
        return new RuntimeParserTransformer()
            ->transform($this, $lexer);
    }
}
