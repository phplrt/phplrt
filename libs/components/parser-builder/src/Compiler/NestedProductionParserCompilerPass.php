<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;

/**
 * Joins a production with the productions of the same kind nested into it.
 *
 * Both concatenation and alternation are associative, so the nested production
 * is an extra step of the analysis and an extra rule of the grammar.
 */
final readonly class NestedProductionParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        do {
            $rules = $context->rules;
            $parents = RuleParents::createFromRules($rules);
            $joined = [];

            foreach ($rules as $rule) {
                if (!$rule instanceof ConcatenationRuleDefinition
                    && !$rule instanceof AlternationRuleDefinition
                ) {
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
     * Returns the rules of the given production with the nested ones joined
     * into it, or {@see null} in case of nothing may be joined.
     *
     * @param list<RuleDefinition> $joined
     * @return list<RuleDefinition>|null
     */
    private function expandChildren(
        AlternationRuleDefinition|ConcatenationRuleDefinition $rule,
        RuleParents $parents,
        ParserBuildingContext $context,
        array &$joined,
    ): ?array {
        $result = [];
        $expanded = false;

        foreach ($rule->children as $child) {
            if (!$this->isJoinable($rule, $child, $parents, $context)) {
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
        AlternationRuleDefinition|ConcatenationRuleDefinition $rule,
        RuleDefinition $child,
        RuleParents $parents,
        ParserBuildingContext $context,
    ): bool {
        /**
         * A named rule is exposed as an identifier of the grammar, a rule with
         * a reducer builds a node of its own and the initial rule is always
         * present in the result, so none of them may be joined.
         */
        if ($child->name !== null || $child->reducer !== null || $child === $context->initial) {
            return false;
        }

        // An alternation passes the value of the matched rule through
        if ($rule instanceof AlternationRuleDefinition) {
            return $child instanceof AlternationRuleDefinition;
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
