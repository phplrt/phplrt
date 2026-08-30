<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\OptionalRuleDefinition;
use Phplrt\Parser\Builder\Definition\PredicateRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;

/**
 * Tells which rules of the grammar may be recognized without reading a token.
 */
final readonly class NullableRules
{
    /**
     * @param \SplObjectStorage<RuleDefinition, bool> $rules
     */
    private function __construct(
        private \SplObjectStorage $rules,
    ) {}

    /**
     * @param list<RuleDefinition> $rules
     */
    public static function createFromRules(array $rules): self
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
                $nullable = self::expand($rule, $result);

                if ($nullable === $result[$rule]) {
                    continue;
                }

                $result[$rule] = $nullable;
                $changed = true;
            }
        } while ($changed);

        return new self($result);
    }

    public function isNullable(RuleDefinition $rule): bool
    {
        return $this->rules[$rule] ?? false;
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, bool> $nullable
     */
    private static function expand(RuleDefinition $rule, \SplObjectStorage $nullable): bool
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
            $rule instanceof OptionalRuleDefinition,
            $rule instanceof PredicateRuleDefinition => true,
            $rule instanceof RepetitionRuleDefinition => $rule->min === 0
                || $nullable[$rule->rule] === true,
            default => false,
        };
    }
}
