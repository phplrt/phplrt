<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
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
        if ($rule === $context->initial) {
            return null;
        }

        $referrer = $parents->findSingleParent($rule);

        if (!$referrer instanceof SequenceRuleDefinitionInterface) {
            return null;
        }

        return $rule->rules[0];
    }
}
