<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\OptionalRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;

/**
 * Writes a repetition that may read nothing, standing in a sequence, as an
 * optional repetition that reads at least once.
 *
 * Both recognize the very same input. A repetition that may read nothing may
 * begin with any token at all, so it is entered at every position the sequence
 * reaches it at, while an optional element of a sequence is told apart by the
 * tokens it begins with without being entered.
 *
 * @readonly
 */
final class OptionalRepetitionParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        $nullable = NullableRules::createFromRules($context->rules);

        /** @var \SplObjectStorage<RuleDefinition, OptionalRuleDefinition> $replacements */
        $replacements = new \SplObjectStorage();

        foreach ($context->rules as $rule) {
            if (!$rule instanceof ConcatenationRuleDefinition) {
                continue;
            }

            foreach ($rule->rules as $element) {
                if ($replacements->offsetExists($element) || !self::isReplaceable($element, $context, $nullable)) {
                    continue;
                }

                \assert($element instanceof RepetitionRuleDefinition);

                $replacements[$element] = new OptionalRuleDefinition(
                    new RepetitionRuleDefinition($element->rule, min: 1, max: $element->max),
                );

                $context->logger->info('Repetition {rule} is read as an optional element of a sequence', [
                    'rule' => $element->printReference(),
                ]);
            }
        }

        if ($replacements->count() === 0) {
            return;
        }

        // The repetition stays where it is written outside of a sequence
        foreach ($context->rules as $rule) {
            if ($rule instanceof ConcatenationRuleDefinition) {
                $rule->replaceChildren(static fn(RuleDefinition $child): RuleDefinition
                    => $replacements->offsetExists($child) ? $replacements[$child] : $child);
            }
        }

        $context->rules = $context->collectReachableRules();
    }

    private static function isReplaceable(
        RuleDefinition $rule,
        ParserBuildingContext $context,
        NullableRules $nullable,
    ): bool {
        if (!$rule instanceof RepetitionRuleDefinition || $rule->min !== 0 || $rule->max < 1) {
            return false;
        }

        /**
         * A named rule is known by its name outside of the grammar, a rule
         * with a reducer builds a node of its own, a rule with a message of
         * its own reports an error nothing else reports, and an entrypoint is
         * always present in the result, so none of them may be written
         * differently.
         */
        if ($rule->name !== null
            || $rule->reducer !== null
            || $rule->message !== null
            || $context->isEntrypoint($rule)
        ) {
            return false;
        }

        /**
         * A body that may read nothing leaves the repetition reading at least
         * once able to begin with any token as well, so there is nothing to
         * tell it apart by.
         *
         * Note: A rejected token is reported at the very position it is
         *       rejected at, while a rejected rule only ahead of the reported
         *       failure, so a repetition of a token is left as it is: the
         *       tokens expected by an error would differ otherwise.
         */
        return !$nullable->isNullable($rule->rule)
            && !$rule->rule instanceof TerminalRuleDefinition;
    }
}
