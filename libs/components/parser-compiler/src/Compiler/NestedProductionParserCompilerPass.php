<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Joins a production with the productions of the same kind nested into it.
 *
 * Both concatenation and alternation are associative, so the nested production
 * is an extra step of the analysis and an extra rule of the grammar.
 */
final readonly class NestedProductionParserCompilerPass implements
    ParserCompilerPassInterface
{
    use HasRuleReplacements;

    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        do {
            $rules = $builder->rules;
            $parents = $this->calculateParents($rules);
            $joined = [];

            foreach ($rules as $rule) {
                if (!$rule instanceof ConcatenationRuleDefinition
                    && !$rule instanceof AlternationRuleDefinition
                ) {
                    continue;
                }

                $children = $this->expandChildren($rule, $parents, $builder, $joined);

                if ($children === null) {
                    continue;
                }

                $rule->setRules($children);
            }

            /**
             * A joined rule is no longer referred to by the rule above, so it
             * must not be added to the grammar on its own.
             */
            foreach ($joined as $rule) {
                $builder->removeRule($rule);
            }
        } while ($joined !== []);
    }

    /**
     * Returns the rules of the given production with the nested ones joined
     * into it, or {@see null} in case of nothing may be joined.
     *
     * @param AlternationRuleDefinition|ConcatenationRuleDefinition $rule
     * @param \SplObjectStorage<RuleDefinition, list<RuleDefinition>> $parents
     * @param list<RuleDefinition> $joined
     * @return list<RuleDefinition>|null
     */
    private function expandChildren(
        RuleDefinition $rule,
        \SplObjectStorage $parents,
        ParserBuilder $builder,
        array &$joined,
    ): ?array {
        $result = [];
        $expanded = false;

        foreach ($rule->children as $child) {
            if (!$this->isJoinable($rule, $child, $parents, $builder)) {
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

    /**
     * @param \SplObjectStorage<RuleDefinition, list<RuleDefinition>> $parents
     */
    private function isJoinable(
        RuleDefinition $rule,
        RuleDefinition $child,
        \SplObjectStorage $parents,
        ParserBuilder $builder,
    ): bool {
        /**
         * A named rule is exposed as an identifier of the grammar, a rule with
         * a reducer builds a node of its own and the initial rule is always
         * present in the result, so none of them may be joined.
         */
        if ($child->name !== null || $child->reducer !== null || $child === $builder->initial) {
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
        return \count($parents[$child] ?? []) === 1;
    }
}
