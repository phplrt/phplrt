<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser;

use Phplrt\Compiler\Parser\Definition\Reducer\CallableReducer;
use Phplrt\Compiler\Parser\Definition\Reducer\ReducerInterface;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Exception\ParserCompilerException;
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
     * Returns the callbacks the parser is created with.
     *
     * @api
     *
     * @return array<int<0, max>, ReducerType&\Closure>
     * @throws ParserCompilerException in case of a reducer cannot be executed
     *         until the parser is generated
     */
    public function createReducerCallbacks(): array
    {
        $result = [];

        foreach ($this->reducers as $rule => $reducer) {
            if (!$reducer instanceof CallableReducer) {
                throw new ParserCompilerException(\sprintf(
                    'Reducer of the rule #%d is defined as PHP code, so the parser '
                        . 'must be generated before it is used',
                    $rule,
                ));
            }

            $result[$rule] = $reducer->callback;
        }

        return $result;
    }
}
