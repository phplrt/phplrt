<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\OptionalRuleDefinition;
use Phplrt\Parser\Builder\Definition\PredicateRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;

/**
 * Checks that no rule of the grammar refers to itself before it recognizes
 * at least one token.
 *
 * Such a rule would be entered over and over again at the very same position
 * of the input, so the analysis would never end.
 */
final readonly class LeftRecursionValidationParserCompilerPass implements
    ParserCompilerPassInterface
{
    /**
     * The rule has not been visited yet.
     */
    private const int STATUS_PENDING = 0;

    /**
     * The rule is being visited, so reaching it again closes the cycle.
     */
    private const int STATUS_ENTERED = 1;

    /**
     * The rule and everything it may begin with is known to be correct.
     */
    private const int STATUS_COMPLETED = 2;

    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        $rules = $context->rules;

        $nullable = NullableRules::createFromRules($rules);

        /** @var \SplObjectStorage<RuleDefinition, int> $statuses */
        $statuses = new \SplObjectStorage();

        foreach ($rules as $rule) {
            $this->validateOrFail($rule, $nullable, $statuses, []);
        }
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, int> $statuses
     * @param list<RuleDefinition> $stack
     * @throws CompilationFailedException
     */
    private function validateOrFail(
        RuleDefinition $rule,
        NullableRules $nullable,
        \SplObjectStorage $statuses,
        array $stack,
    ): void {
        $status = $statuses[$rule] ?? self::STATUS_PENDING;

        if ($status === self::STATUS_COMPLETED) {
            return;
        }

        if ($status === self::STATUS_ENTERED) {
            throw new CompilationFailedException($rule, \sprintf(
                'Rule %s is left recursive: %s',
                $rule,
                $this->printCycle($rule, $stack),
            ));
        }

        $statuses[$rule] = self::STATUS_ENTERED;
        $stack[] = $rule;

        foreach ($this->getLeadingRules($rule, $nullable) as $leading) {
            $this->validateOrFail($leading, $nullable, $statuses, $stack);
        }

        $statuses[$rule] = self::STATUS_COMPLETED;
    }

    /**
     * Returns the rules that may be entered before the given one recognizes
     * a token.
     *
     * @return list<RuleDefinition>
     */
    private function getLeadingRules(RuleDefinition $rule, NullableRules $nullable): array
    {
        if ($rule instanceof ConcatenationRuleDefinition) {
            $result = [];

            foreach ($rule->rules as $inner) {
                $result[] = $inner;

                // Everything behind a rule that recognizes a token is reached
                // only after the input has moved forward
                if (!$nullable->isNullable($inner)) {
                    break;
                }
            }

            return $result;
        }

        return match (true) {
            $rule instanceof AlternationRuleDefinition => $rule->rules,
            /**
             * A predicate reads nothing, so the rule behind it is reached at
             * the very same position the predicate has been entered at.
             */
            $rule instanceof OptionalRuleDefinition,
            $rule instanceof PredicateRuleDefinition,
            $rule instanceof RepetitionRuleDefinition => [$rule->rule],
            default => [],
        };
    }

    /**
     * @param list<RuleDefinition> $stack
     * @return non-empty-string
     */
    private function printCycle(RuleDefinition $rule, array $stack): string
    {
        $references = [];
        $started = false;

        foreach ($stack as $visited) {
            $started = $started || $visited === $rule;

            if ($started) {
                $references[] = $visited->printReference();
            }
        }

        $references[] = $rule->printReference();

        return \implode(' -> ', $references);
    }
}
