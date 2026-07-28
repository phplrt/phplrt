<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;

/**
 * Removes the alternatives that repeat an earlier one.
 *
 * The alternatives are tried in the order they are written and the first one
 * that is recognized wins, so an alternative repeating an earlier one is never
 * reached and only costs an attempt at every input the rule above it is tried
 * on.
 */
final readonly class RepeatedAlternativeParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        $replacements = new RuleReplacements();

        foreach ($context->rules as $rule) {
            if (!$rule instanceof AlternationRuleDefinition) {
                continue;
            }

            $rules = self::removeRepeated($rule->rules);

            if (\count($rules) === \count($rule->rules)) {
                continue;
            }

            $rule->setRules($rules);

            /**
             * An alternation left with a single rule recognizes exactly what
             * that rule does, unless it builds a node of its own.
             */
            if ($rule->reducer === null && \count($rules) === 1) {
                $replacements->replace($rule, $rules[0]);
            }
        }

        $replacements->applyTo($context);
    }

    /**
     * Returns the given alternatives with the ones repeating an earlier
     * alternative dropped.
     *
     * @param list<RuleDefinition> $rules
     * @return list<RuleDefinition>
     */
    private static function removeRepeated(array $rules): array
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $reached */
        $reached = new \SplObjectStorage();

        $result = [];

        foreach ($rules as $rule) {
            if ($reached->offsetExists($rule)) {
                continue;
            }

            $reached->offsetSet($rule);

            $result[] = $rule;
        }

        return $result;
    }
}
