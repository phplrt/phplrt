<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;

/**
 * Joins a concatenation with the concatenations nested into it.
 *
 * A concatenation is associative, so the nested one is an extra step of the
 * analysis and an extra rule of the grammar while the rules of both of them are
 * recognized one after another all the same.
 */
final readonly class NestedConcatenationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        do {
            $rules = $context->rules;
            $parents = RuleParents::createFromRules($rules);
            $joined = [];

            foreach ($rules as $rule) {
                /**
                 * [!NOTE!] An alternation is associative as well, but joining
                 *          it with the alternation nested into it makes the
                 *          parser SLOWER instead of faster.
                 *
                 *          The analysis skips a rule as soon as the token it
                 *          reads cannot start it, so a nested alternation is
                 *          skipped along with every rule of it by a single
                 *          lookup in the lookahead table. Joining the two turns
                 *          that single lookup into a lookup per rule, and a
                 *          grammar fails to recognize an alternative far more
                 *          often than it recognizes one.
                 */
                if (!$rule instanceof ConcatenationRuleDefinition) {
                    continue;
                }

                $children = $this->expandChildren($rule, $parents, $context, $joined);

                if ($children === null) {
                    continue;
                }

                $rule->setRules($children);
            }

            /**
             * A joined rule is no longer referred to by the rule above, so it
             * must not stay in the grammar on its own.
             */
            if ($joined !== []) {
                $context->rules = $context->initial?->collectRules() ?? [];
            }
        } while ($joined !== []);
    }

    /**
     * Returns the rules of the given concatenation with the nested ones joined
     * into it, or {@see null} in case of nothing may be joined.
     *
     * @param list<RuleDefinition> $joined
     * @return list<RuleDefinition>|null
     */
    private function expandChildren(
        ConcatenationRuleDefinition $rule,
        RuleParents $parents,
        ParserBuildingContext $context,
        array &$joined,
    ): ?array {
        $result = [];
        $expanded = false;

        foreach ($rule->children as $child) {
            if (!$this->isJoinable($child, $parents, $context)) {
                $result[] = $child;

                continue;
            }

            foreach ($child->children as $inner) {
                $result[] = $inner;
            }

            $joined[] = $child;
            $expanded = true;
        }

        return $expanded ? $result : null;
    }

    private function isJoinable(
        RuleDefinition $child,
        RuleParents $parents,
        ParserBuildingContext $context,
    ): bool {
        /**
         * A rule with a reducer builds a node of its own and the initial rule
         * is always present in the result, so neither may be joined.
         */
        if ($child->reducer !== null || $child === $context->initial) {
            return false;
        }

        if (!$child instanceof ConcatenationRuleDefinition) {
            return false;
        }

        /**
         * The values of a nested concatenation are joined with the values of
         * the rule above only while it is the only rule referring to it.
         */
        return $parents->findSingleParent($child) !== null;
    }
}
