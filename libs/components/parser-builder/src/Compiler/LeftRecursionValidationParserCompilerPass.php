<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\OptionalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RepetitionRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;

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

        $nullable = $this->calculateNullable($rules);

        /** @var \SplObjectStorage<RuleDefinition, int> $statuses */
        $statuses = new \SplObjectStorage();

        foreach ($rules as $rule) {
            $this->validateOrFail($rule, $nullable, $statuses, []);
        }
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, bool> $nullable
     * @param \SplObjectStorage<RuleDefinition, int> $statuses
     * @param list<RuleDefinition> $stack
     * @throws CompilationFailedException
     */
    private function validateOrFail(
        RuleDefinition $rule,
        \SplObjectStorage $nullable,
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
     * @param \SplObjectStorage<RuleDefinition, bool> $nullable
     * @return list<RuleDefinition>
     */
    private function getLeadingRules(RuleDefinition $rule, \SplObjectStorage $nullable): array
    {
        if ($rule instanceof ConcatenationRuleDefinition) {
            $result = [];

            foreach ($rule->rules as $inner) {
                $result[] = $inner;

                // Everything behind a rule that recognizes a token is reached
                // only after the input has moved forward
                if ($nullable[$inner] !== true) {
                    break;
                }
            }

            return $result;
        }

        return match (true) {
            $rule instanceof AlternationRuleDefinition => $rule->rules,
            $rule instanceof OptionalRuleDefinition,
            $rule instanceof RepetitionRuleDefinition => [$rule->rule],
            default => [],
        };
    }

    /**
     * Returns the rules that may be recognized without consuming a token.
     *
     * @param list<RuleDefinition> $rules
     * @return \SplObjectStorage<RuleDefinition, bool>
     */
    private function calculateNullable(array $rules): \SplObjectStorage
    {
        /** @var \SplObjectStorage<RuleDefinition, bool> $result */
        $result = new \SplObjectStorage();

        foreach ($rules as $rule) {
            $result[$rule] = false;
        }

        // The rules refer to each other, so the values change until they stop
        do {
            $changed = false;

            foreach ($rules as $rule) {
                $nullable = $this->isNullable($rule, $result);

                if ($nullable === $result[$rule]) {
                    continue;
                }

                $result[$rule] = $nullable;
                $changed = true;
            }
        } while ($changed);

        return $result;
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, bool> $nullable
     */
    private function isNullable(RuleDefinition $rule, \SplObjectStorage $nullable): bool
    {
        if ($rule instanceof ConcatenationRuleDefinition) {
            foreach ($rule->rules as $inner) {
                if ($nullable[$inner] !== true) {
                    return false;
                }
            }

            return true;
        }

        if ($rule instanceof AlternationRuleDefinition) {
            foreach ($rule->rules as $inner) {
                if ($nullable[$inner] === true) {
                    return true;
                }
            }

            return false;
        }

        return match (true) {
            $rule instanceof OptionalRuleDefinition => true,
            $rule instanceof RepetitionRuleDefinition => $rule->min === 0
                || $nullable[$rule->rule] === true,
            default => false,
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
