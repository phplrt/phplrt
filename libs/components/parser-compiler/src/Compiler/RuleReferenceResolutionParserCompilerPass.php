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
        $initial = $builder->initial;

        if ($initial instanceof RuleReference) {
            $builder->setInitialRule($this->resolveRule($initial, $initial, $this->collectNamedRules($builder)));
        }

        /**
         * A resolved reference may attach a rule referring to another one by
         * name, so the grammar is walked again until nothing changes.
         */
        do {
            $named = $this->collectNamedRules($builder);
            $resolved = false;

            foreach ($builder->rules as $rule) {
                $resolved = $this->resolveChildren($rule, $named) || $resolved;
            }
        } while ($resolved);

        /**
         * A reference stands for the rule it points at, so it must not reach
         * the grammar as a rule of its own.
         */
        foreach ([...$builder->declarations] as $declaration) {
            if ($declaration instanceof RuleReference) {
                $builder->removeRule($declaration);
            }
        }
    }

    /**
     * Returns {@see true} in case of at least one reference has been replaced.
     *
     * @param array<non-empty-string, RuleDefinition> $named
     * @throws CompilationFailedException
     */
    private function resolveChildren(RuleDefinition $rule, array $named): bool
    {
        $children = $rule->children;

        if ($children === []) {
            return false;
        }

        foreach ($children as $child) {
            if ($child instanceof RuleReference) {
                $this->replaceChildren($rule, $named);

                return true;
            }
        }

        return false;
    }

    /**
     * @param array<non-empty-string, RuleDefinition> $named
     * @throws CompilationFailedException
     */
    private function replaceChildren(RuleDefinition $rule, array $named): void
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
        /** @var \SplObjectStorage<RuleReference, null> $visited */
        $visited = new \SplObjectStorage();

        // A reference may point at another one, so the chain is followed to
        // the rule it ends at
        while ($child instanceof RuleReference) {
            if ($visited->offsetExists($child)) {
                throw new CompilationFailedException($rule, \sprintf(
                    'Rule %s refers to itself through a chain of references',
                    $rule,
                ));
            }

            $visited->offsetSet($child);

            $target = $child->target;

            $child = \is_string($target)
                ? $named[$target] ?? throw new CompilationFailedException($rule, \sprintf(
                    'Rule %s refers to the rule named "%s", which has not been defined',
                    $rule,
                    $target,
                ))
                : $target;
        }

        return $child;
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
