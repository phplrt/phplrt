<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Builder\Definition\Reducer\ReducerInterface;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\Transformer\RuntimeParserTransformer;
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
        public array $startTokens,
        /**
         * The rules that may be recognized without consuming a token, indexed
         * by the rule identifiers.
         *
         * @var array<int, bool>
         */
        public array $matchesEmptyInput,
        /**
         * The rules that are present in the resulting tree, indexed by the rule
         * identifiers.
         *
         * @var array<int, bool>
         */
        public array $presentInTree,
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
    ) {}

    /**
     * @throws ParserCompilerException in case of the grammar cannot be run
     */
    public function toParser(LexerInterface $lexer): ParserInterface
    {
        return new RuntimeParserTransformer()
            ->transform($this, $lexer);
    }
}
