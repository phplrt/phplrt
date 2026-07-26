<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Definition\SequenceRuleDefinitionInterface;
use Phplrt\Compiler\Parser\ParserBuilder;

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
    use HasRuleReplacements;

    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        do {
            $rules = $builder->rules;
            $parents = $this->calculateParents($rules);

            /** @var \SplObjectStorage<RuleDefinition, RuleDefinition> $replacements */
            $replacements = new \SplObjectStorage();

            foreach ($rules as $rule) {
                $child = $this->findRedundantChild($rule, $parents, $builder);

                if ($child === null) {
                    continue;
                }

                $replacements[$rule] = $child;
            }

            $this->replaceRules($builder, $rules, $replacements);
        } while ($replacements->count() > 0);
    }

    /**
     * Returns the rule the given production is equivalent to, or {@see null}
     * in case of the production cannot be removed.
     *
     * @param \SplObjectStorage<RuleDefinition, list<RuleDefinition>> $parents
     */
    private function findRedundantChild(
        RuleDefinition $rule,
        \SplObjectStorage $parents,
        ParserBuilder $builder,
    ): ?RuleDefinition {
        /**
         * A named rule is exposed as an identifier of the grammar and a rule
         * with a reducer builds a node of its own, so neither may be removed.
         */
        if ($rule->name !== null || $rule->reducer !== null) {
            return null;
        }

        // An alternation passes the value of the matched rule through
        if ($rule instanceof AlternationRuleDefinition) {
            return \count($rule->rules) === 1 ? $rule->rules[0] : null;
        }

        if (!$rule instanceof ConcatenationRuleDefinition || \count($rule->rules) !== 1) {
            return null;
        }

        /**
         * A concatenation recognizes a sequence of values, so removing it keeps
         * the result the same only while its value is joined with the values of
         * the rule that refers to it.
         */
        if ($rule === $builder->initial) {
            return null;
        }

        $referrers = $parents[$rule] ?? [];

        if (\count($referrers) !== 1 || !$referrers[0] instanceof SequenceRuleDefinitionInterface) {
            return null;
        }

        return $rule->rules[0];
    }
}
