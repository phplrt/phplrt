<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Contains the grammar the compiler passes rewrite and check.
 *
 * The rule definitions are copies of the ones the builder has been given, so a
 * pass is free to rewrite them.
 */
final class ParserBuildingContext
{
    public function __construct(
        /**
         * Contains the rule the analysis starts at, or {@see null} in case of
         * the grammar contains no rules
         */
        public ?RuleDefinition $initial = null,
        /**
         * The rules of the grammar.
         *
         * A pass rewriting the grammar is the one keeping this list in sync
         * with the rules the initial one refers to.
         *
         * @var list<RuleDefinition>
         */
        public array $rules = [],
        /**
         * Reports what the passes do to the rules of the grammar.
         */
        public readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Tells whether the given rule is kept.
     */
    public function isKept(RuleDefinition $rule): bool
    {
        if ($rule === $this->initial) {
            return true;
        }

        return !$rule instanceof TerminalRuleDefinition && $rule->isKept;
    }

    /**
     * Returns the rules reached from the kept ones, in the order they are
     * reached.
     *
     * @return list<RuleDefinition>
     */
    public function collectReachableRules(): array
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $reached */
        $reached = new \SplObjectStorage();

        foreach ($this->collectKeptRules() as $kept) {
            foreach ($kept->collectRules() as $rule) {
                $reached->offsetSet($rule);
            }
        }

        /** @var list<RuleDefinition> */
        return \iterator_to_array($reached, false);
    }

    /**
     * Returns the kept rules, the initial one first.
     *
     * @return list<RuleDefinition>
     */
    private function collectKeptRules(): array
    {
        $result = $this->initial === null ? [] : [$this->initial];

        foreach ($this->rules as $rule) {
            if ($this->isKept($rule) && $rule !== $this->initial) {
                $result[] = $rule;
            }
        }

        return $result;
    }
}
