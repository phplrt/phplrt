<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;

/**
 * Joins a repetition with the repetition nested into it.
 *
 * Repeating what is already repeated recognizes the very same input: the inner
 * rule is greedy, so the first occurrence reads everything there is and the
 * outer repetition stops right after it. The inner repetition is therefore an
 * extra step of the analysis and an extra rule of the grammar.
 */
final readonly class NestedRepetitionParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        do {
            $joined = false;

            foreach ($context->rules as $rule) {
                $child = self::findRepeatedRule($rule, $context);

                if ($child === null) {
                    continue;
                }

                \assert($rule instanceof RepetitionRuleDefinition);

                $rule->setRule($child);

                $joined = true;
            }

            /**
             * A joined rule is no longer referred to by the rule above, so it
             * must not stay in the grammar on its own.
             */
            if ($joined) {
                $context->rules = $context->initial?->collectRules() ?? [];
            }
        } while ($joined);
    }

    /**
     * Returns the rule the given repetition recognizes once the repetition
     * nested into it is joined with it, or {@see null} in case of nothing may
     * be joined.
     */
    private static function findRepeatedRule(
        RuleDefinition $rule,
        ParserBuildingContext $context,
    ): ?RuleDefinition {
        if (!$rule instanceof RepetitionRuleDefinition || !self::isUnbounded($rule)) {
            return null;
        }

        $child = $rule->rule;

        if (!$child instanceof RepetitionRuleDefinition || !self::isUnbounded($child)) {
            return null;
        }

        /**
         * A rule with a reducer builds a node of its own and the initial rule
         * is always present in the result, so neither may be joined.
         */
        if ($child->reducer !== null || $child === $context->initial) {
            return null;
        }

        return $child->rule;
    }

    /**
     * Tells whether the given repetition recognizes the rule as many times as
     * the input matches it, and demands no more than a single occurrence.
     *
     * A repetition demanding several occurrences is never satisfied by a
     * greedy one nested into it, and a nested repetition demanding more than
     * one occurrence recognizes nothing where the rule above it recognizes an
     * empty input, so neither of them says what the two of them together do.
     */
    private static function isUnbounded(RepetitionRuleDefinition $rule): bool
    {
        return \is_infinite($rule->max) && $rule->min <= 1;
    }
}
