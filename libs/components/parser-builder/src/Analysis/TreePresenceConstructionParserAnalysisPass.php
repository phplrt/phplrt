<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Analysis;

use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Predicate;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\SequenceInterface;

/**
 * Describes the rules that must be present in the resulting tree.
 */
final readonly class TreePresenceConstructionParserAnalysisPass implements
    ParserAnalysisPassInterface
{
    public function process(ParserResultContext $context): void
    {
        $grammar = $context->grammar;
        $reducers = $context->reducers;

        $result = [];

        foreach ($grammar as $rule => $definition) {
            // A rule containing a single value passes it through as is, so
            // without a reducer it adds nothing to the tree
            $result[$rule] = isset($reducers[$rule])
                || $definition instanceof SequenceInterface;
        }

        $parents = $this->calculateParents($grammar);

        foreach ($grammar as $rule => $definition) {
            if ($rule === $context->initial
                || isset($reducers[$rule])
                || !$definition instanceof SequenceInterface
            ) {
                continue;
            }

            $observers = $this->findObservers($rule, $context->initial, $parents, $result, []);

            if ($observers === []) {
                continue;
            }

            foreach ($observers as $observer) {
                if (!$grammar[$observer] instanceof SequenceInterface) {
                    continue 2;
                }
            }

            // Everyone who sees the value of the rule joins it with the values
            // of its neighbours anyway, so the rule itself adds nothing
            $result[$rule] = false;
        }

        $context->presentInTree = $result;
    }

    /**
     * Returns the rules referring to each rule of the grammar.
     *
     * @param list<RuleInterface> $grammar
     * @return array<int, list<int>>
     */
    private function calculateParents(array $grammar): array
    {
        $result = [];

        foreach ($grammar as $rule => $definition) {
            $children = match (true) {
                $definition instanceof Concatenation => $definition->ruleIds,
                $definition instanceof Alternation => $definition->ruleIds,
                $definition instanceof Optional,
                $definition instanceof Predicate,
                $definition instanceof Repetition => [$definition->ruleId],
                default => [],
            };

            foreach ($children as $child) {
                $result[$child][] = $rule;
            }
        }

        return $result;
    }

    /**
     * Returns the rules that will see the value of the given one.
     *
     * @param array<int, list<int>> $parents
     * @param array<int, bool> $presentInTree
     * @param array<int, true> $visited
     * @return list<int>
     */
    private function findObservers(int $rule, int $initial, array $parents, array $presentInTree, array $visited): array
    {
        if (isset($visited[$rule])) {
            return [];
        }

        $visited[$rule] = true;
        $result = [];

        foreach ($parents[$rule] ?? [] as $parent) {
            /**
             * The value of the initial rule is the result of the analysis, so
             * there is nothing above it to join the value with.
             */
            if ($presentInTree[$parent] || $parent === $initial) {
                $result[] = $parent;

                continue;
            }

            // A rule missing from the tree passes the value to its own observers
            foreach ($this->findObservers($parent, $initial, $parents, $presentInTree, $visited) as $observer) {
                $result[] = $observer;
            }
        }

        return $result;
    }
}
