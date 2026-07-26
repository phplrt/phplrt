<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\ParseTree;

use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\TraceReducer;

/**
 * TODO Move to compiler
 *
 * @phpstan-import-type ReducerType from TraceReducer
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class KeptConstruction
{
    /**
     * Returns the rules that must be present in the resulting tree.
     *
     * @param list<RuleInterface> $grammar
     * @param array<int<0, max>, ReducerType> $reducers
     * @param int<0, max> $initial
     * @return array<int, bool>
     */
    public static function compute(array $grammar, array $reducers, int $initial): array
    {
        $result = [];

        foreach ($grammar as $rule => $definition) {
            // A rule containing a single value passes it through as is, so
            // without a reducer it adds nothing to the tree
            $result[$rule] = isset($reducers[$rule])
                || !($definition instanceof Alternation
                    || $definition instanceof Optional
                    || $definition instanceof Lexeme);
        }

        $parents = self::calculateParents($grammar);

        foreach ($grammar as $rule => $definition) {
            if ($rule === $initial || isset($reducers[$rule]) || !self::isMerged($definition)) {
                continue;
            }

            $observers = self::findObservers($rule, $parents, $result, []);

            if ($observers === []) {
                continue;
            }

            foreach ($observers as $observer) {
                if (!self::isMerged($grammar[$observer])) {
                    continue 2;
                }
            }

            // Everyone who sees the value of the rule joins it with the values
            // of its neighbours anyway, so the rule itself adds nothing
            $result[$rule] = false;
        }

        return $result;
    }

    private static function isMerged(RuleInterface $definition): bool
    {
        return $definition instanceof Concatenation
            || $definition instanceof Repetition;
    }

    /**
     * Returns the rules referring to each rule of the grammar.
     *
     * @param list<RuleInterface> $grammar
     * @return array<int, list<int>>
     */
    private static function calculateParents(array $grammar): array
    {
        $result = [];

        foreach ($grammar as $rule => $definition) {
            $children = match (true) {
                $definition instanceof Concatenation => $definition->rules,
                $definition instanceof Alternation => $definition->ruleIds,
                $definition instanceof Optional,
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
     * @param array<int, bool> $kept
     * @param array<int, true> $visited
     * @return list<int>
     */
    private static function findObservers(int $rule, array $parents, array $kept, array $visited): array
    {
        if (isset($visited[$rule])) {
            return [];
        }

        $visited[$rule] = true;
        $result = [];

        foreach ($parents[$rule] ?? [] as $parent) {
            if ($kept[$parent]) {
                $result[] = $parent;

                continue;
            }

            // A rule missing from the tree passes the value to its own observers
            foreach (self::findObservers($parent, $parents, $kept, $visited) as $observer) {
                $result[] = $observer;
            }
        }

        return $result;
    }
}
