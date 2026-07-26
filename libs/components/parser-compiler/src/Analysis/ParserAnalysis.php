<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Analysis;

use Phplrt\Compiler\Parser\Definition\Reducer\ReducerInterface;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Describes the grammar the parser is built from.
 */
final class ParserAnalysis
{
    /**
     * The identifiers of the tokens a rule may begin with, indexed by the rule
     * identifiers.
     *
     * @var array<int, array<int, true>>
     */
    public private(set) array $first = [];

    /**
     * The rules that may be recognized without consuming a token, indexed by
     * the rule identifiers.
     *
     * @var array<int, bool>
     */
    public private(set) array $nullable = [];

    /**
     * The rules that are present in the resulting tree, indexed by the rule
     * identifiers.
     *
     * @var array<int, bool>
     */
    public private(set) array $kept = [];

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
    ) {}

    /**
     * @param array<int, array<int, true>> $first
     * @return $this
     */
    public function setFirst(array $first): self
    {
        $this->first = $first;

        return $this;
    }

    /**
     * @param array<int, bool> $nullable
     * @return $this
     */
    public function setNullable(array $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * @param array<int, bool> $kept
     * @return $this
     */
    public function setKept(array $kept): self
    {
        $this->kept = $kept;

        return $this;
    }
}
