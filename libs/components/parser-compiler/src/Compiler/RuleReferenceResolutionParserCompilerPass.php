<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\OptionalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RepetitionRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleReference;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Replaces every reference by the rule it points at.
 */
final readonly class RuleReferenceResolutionParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        $named = $this->collectNamedRules($builder);
        $references = [];

        foreach ($builder->rules as $rule) {
            if ($rule instanceof RuleReference) {
                $references[] = $rule;

                continue;
            }

            $this->resolveChildren($rule, $named);
        }

        $initial = $builder->initial;

        if ($initial instanceof RuleReference) {
            $builder->setInitialRule($this->resolveRule($initial, $initial, $named));
        }

        foreach ($references as $reference) {
            $builder->removeRule($reference);
        }
    }

    /**
     * @param array<non-empty-string, RuleDefinition> $named
     * @throws CompilationFailedException
     */
    private function resolveChildren(RuleDefinition $rule, array $named): void
    {
        match (true) {
            $rule instanceof ConcatenationRuleDefinition,
            $rule instanceof AlternationRuleDefinition => $rule->setRules(
                $this->resolveRules($rule, $rule->rules, $named),
            ),
            $rule instanceof OptionalRuleDefinition,
            $rule instanceof RepetitionRuleDefinition => $rule->setRule(
                $this->resolveRule($rule, $rule->rule, $named),
            ),
            default => null,
        };
    }

    /**
     * @param list<RuleDefinition> $rules
     * @param array<non-empty-string, RuleDefinition> $named
     * @return list<RuleDefinition>
     * @throws CompilationFailedException
     */
    private function resolveRules(RuleDefinition $rule, array $rules, array $named): array
    {
        $result = [];

        foreach ($rules as $child) {
            $result[] = $this->resolveRule($rule, $child, $named);
        }

        return $result;
    }

    /**
     * @param array<non-empty-string, RuleDefinition> $named
     * @throws CompilationFailedException
     */
    private function resolveRule(RuleDefinition $rule, RuleDefinition $child, array $named): RuleDefinition
    {
        if (!$child instanceof RuleReference) {
            return $child;
        }

        return $named[$child->target] ?? throw new CompilationFailedException($rule, \sprintf(
            'Rule %s refers to the rule named "%s", which has not been defined',
            $rule,
            $child->target,
        ));
    }

    /**
     * @return array<non-empty-string, RuleDefinition>
     */
    private function collectNamedRules(ParserBuilder $builder): array
    {
        $result = [];

        foreach ($builder->rules as $rule) {
            if ($rule->name === null) {
                continue;
            }

            $result[$rule->name] = $rule;
        }

        return $result;
    }
}
