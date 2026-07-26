<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleReference;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;

/**
 * Replaces every reference by the rule it points at.
 */
final readonly class RuleReferenceResolutionParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        $initial = $context->initial;

        if ($initial instanceof RuleReference) {
            $context->initial = $this->resolveRule($initial, $initial, $this->collectNamedRules($context));
        }

        /**
         * A resolved reference may attach a rule referring to another one by
         * name, so the grammar is walked again until nothing changes.
         */
        do {
            $named = $this->collectNamedRules($context);
            $resolved = false;

            foreach ($context->rules as $rule) {
                // A reference points at the rule instead of containing it
                if ($rule instanceof RuleReference) {
                    continue;
                }

                $resolved = $this->resolveChildren($rule, $named) || $resolved;
            }
        } while ($resolved);

        /**
         * A reference stands for the rule it points at, so it must not reach
         * the grammar as a rule of its own.
         */
        $context->rules = \array_values(\array_filter(
            $context->rules,
            static fn(RuleDefinition $rule): bool => !$rule instanceof RuleReference,
        ));
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
                $rule->replaceChildren(fn(RuleDefinition $inner): RuleDefinition
                    => $this->resolveRule($rule, $inner, $named));

                return true;
            }
        }

        return false;
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
    private function collectNamedRules(ParserBuildingContext $context): array
    {
        $result = [];

        foreach ($context->rules as $rule) {
            if ($rule->name === null) {
                continue;
            }

            $result[$rule->name] = $rule;
        }

        return $result;
    }
}
