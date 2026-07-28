<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\OptionalRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\SequenceRuleDefinitionInterface;

/**
 * Removes the productions of a single rule that recognize exactly what that
 * rule does.
 *
 * Such a production is an extra step of the analysis and an extra rule of the
 * grammar, so it is replaced by the rule it refers to.
 */
final readonly class RedundantProductionParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        do {
            $parents = RuleParents::createFromRules($context->rules);
            $replacements = new RuleReplacements();

            foreach ($context->rules as $rule) {
                $child = $this->findRedundantChild($rule, $parents, $context);

                if ($child === null) {
                    continue;
                }

                $replacements->replace($rule, $child);
            }

            $replacements->applyTo($context);
        } while (!$replacements->isEmpty);
    }

    /**
     * Returns the rule the given production is equivalent to, or {@see null}
     * in case of the production cannot be removed.
     */
    private function findRedundantChild(
        RuleDefinition $rule,
        RuleParents $parents,
        ParserBuildingContext $context,
    ): ?RuleDefinition {
        // A rule with a reducer builds a node of its own, so it may not be
        // removed
        if ($rule->reducer !== null) {
            return null;
        }

        // An alternation passes the value of the matched rule through
        if ($rule instanceof AlternationRuleDefinition) {
            return \count($rule->rules) === 1 ? $rule->rules[0] : null;
        }

        /**
         * An optional rule passes the value of the rule it contains through,
         * and a rule that is recognized no matter what the input is leaves
         * nothing for the optional one to decide.
         */
        if ($rule instanceof OptionalRuleDefinition) {
            /** @var \SplObjectStorage<RuleDefinition, bool> $matches */
            $matches = new \SplObjectStorage();

            return self::alwaysMatches($rule->rule, $matches) ? $rule->rule : null;
        }

        $child = self::findSingleSequenceChild($rule);

        if ($child === null) {
            return null;
        }

        /**
         * A sequence recognizes a list of values, so removing it keeps the
         * result the same only while its value is joined with the values of the
         * rules that refer to it. The value of the initial rule is the result
         * of the analysis, so there is nothing above it to join with.
         */
        if ($rule === $context->initial) {
            return null;
        }

        /** @var \SplObjectStorage<RuleDefinition, null> $visited */
        $visited = new \SplObjectStorage();

        return self::isJoinedByParents($rule, $parents, $context, $visited)
            ? $child
            : null;
    }

    /**
     * Returns the only rule the given sequence recognizes, or {@see null} in
     * case of the rule recognizes anything else.
     */
    private static function findSingleSequenceChild(RuleDefinition $rule): ?RuleDefinition
    {
        if ($rule instanceof ConcatenationRuleDefinition) {
            return \count($rule->rules) === 1 ? $rule->rules[0] : null;
        }

        // A rule recognized exactly once is the rule itself
        if ($rule instanceof RepetitionRuleDefinition && $rule->min === 1 && $rule->max === 1) {
            return $rule->rule;
        }

        return null;
    }

    /**
     * Tells whether the given rule is recognized no matter what the input is.
     *
     * @param \SplObjectStorage<RuleDefinition, bool> $matches what is already
     *        known about the rules, so that a rule reached through itself is
     *        not asked about forever
     */
    private static function alwaysMatches(RuleDefinition $rule, \SplObjectStorage $matches): bool
    {
        if ($matches->offsetExists($rule)) {
            return $matches[$rule];
        }

        // A rule reached through itself is assumed to recognize nothing until
        // it is known what it recognizes
        $matches->offsetSet($rule, false);
        $matches->offsetSet($rule, self::calculateAlwaysMatches($rule, $matches));

        return $matches[$rule];
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, bool> $matches
     */
    private static function calculateAlwaysMatches(RuleDefinition $rule, \SplObjectStorage $matches): bool
    {
        // Recognizing nothing at all is a match of its own
        if ($rule instanceof OptionalRuleDefinition) {
            return true;
        }

        if ($rule instanceof RepetitionRuleDefinition) {
            return $rule->min === 0;
        }

        // An alternation is recognized as soon as one of its rules is
        if ($rule instanceof AlternationRuleDefinition) {
            foreach ($rule->rules as $child) {
                if (self::alwaysMatches($child, $matches)) {
                    return true;
                }
            }

            return false;
        }

        // A concatenation is recognized while every rule of it is
        if ($rule instanceof ConcatenationRuleDefinition) {
            foreach ($rule->rules as $child) {
                if (!self::alwaysMatches($child, $matches)) {
                    return false;
                }
            }

            return true;
        }

        /**
         * A terminal is recognized only while the input contains the token it
         * stands for, and a predicate is the very rule made to fail, so
         * neither of them is a match on its own.
         */
        return false;
    }

    /**
     * Tells whether everything that will see the value of the given rule joins
     * it with the values of its neighbours.
     *
     * A rule passing a value through is not the one seeing it, so the rules
     * above it are asked instead: the value is only seen where it is either
     * joined with the others or handed to a reducer.
     *
     * @param \SplObjectStorage<RuleDefinition, null> $visited
     */
    private static function isJoinedByParents(
        RuleDefinition $rule,
        RuleParents $parents,
        ParserBuildingContext $context,
        \SplObjectStorage $visited,
    ): bool {
        // A rule reached along a path that has already been walked adds nothing
        // to what is known about it
        if ($visited->offsetExists($rule)) {
            return true;
        }

        $visited->offsetSet($rule);

        $referrers = $parents->findParents($rule);

        // A rule nothing refers to is the result of the analysis on its own
        if ($referrers === []) {
            return false;
        }

        foreach ($referrers as $referrer) {
            if ($referrer instanceof SequenceRuleDefinitionInterface) {
                continue;
            }

            /**
             * A reducer is given the value as it is, and the value of the
             * initial rule is the result of the analysis, so both of them see
             * the value instead of passing it through.
             */
            if ($referrer->reducer !== null || $referrer === $context->initial) {
                return false;
            }

            if (!self::isJoinedByParents($referrer, $parents, $context, $visited)) {
                return false;
            }
        }

        return true;
    }
}
