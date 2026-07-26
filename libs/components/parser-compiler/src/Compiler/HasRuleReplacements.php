<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\OptionalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RepetitionRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Replaces the rules of the grammar by the ones they are equivalent to.
 *
 * @internal this is an internal library trait, please do not use it in your code
 * @psalm-internal Phplrt\Compiler\Parser
 */
trait HasRuleReplacements
{
    /**
     * Returns the rules referring to each rule of the grammar.
     *
     * @param list<RuleDefinition> $rules
     * @return \SplObjectStorage<RuleDefinition, list<RuleDefinition>>
     */
    private function calculateParents(array $rules): \SplObjectStorage
    {
        /** @var \SplObjectStorage<RuleDefinition, list<RuleDefinition>> $result */
        $result = new \SplObjectStorage();

        foreach ($rules as $rule) {
            foreach ($rule->children as $child) {
                $parents = $result[$child] ?? [];

                $parents[] = $rule;

                $result[$child] = $parents;
            }
        }

        return $result;
    }

    /**
     * Replaces every occurrence of the given rules, including the initial rule
     * and the declarations.
     *
     * @param list<RuleDefinition> $rules
     * @param \SplObjectStorage<RuleDefinition, RuleDefinition> $replacements
     */
    private function replaceRules(
        ParserBuilder $builder,
        array $rules,
        \SplObjectStorage $replacements,
    ): void {
        if ($replacements->count() === 0) {
            return;
        }

        foreach ($rules as $rule) {
            $this->replaceChildren($rule, $replacements);
        }

        $initial = $builder->initial;

        if ($initial !== null) {
            $builder->setInitialRule($this->resolveReplacement($initial, $replacements));
        }

        foreach ([...$builder->declarations] as $declaration) {
            $replacement = $this->resolveReplacement($declaration, $replacements);

            if ($replacement === $declaration) {
                continue;
            }

            $builder->removeRule($declaration);
            $builder->addRule($replacement);
        }
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, RuleDefinition> $replacements
     */
    private function replaceChildren(RuleDefinition $rule, \SplObjectStorage $replacements): void
    {
        match (true) {
            $rule instanceof ConcatenationRuleDefinition,
            $rule instanceof AlternationRuleDefinition => $rule->setRules(
                $this->resolveReplacements($rule->rules, $replacements),
            ),
            $rule instanceof OptionalRuleDefinition,
            $rule instanceof RepetitionRuleDefinition => $rule->setRule(
                $this->resolveReplacement($rule->rule, $replacements),
            ),
            default => null,
        };
    }

    /**
     * @param list<RuleDefinition> $rules
     * @param \SplObjectStorage<RuleDefinition, RuleDefinition> $replacements
     * @return list<RuleDefinition>
     */
    private function resolveReplacements(array $rules, \SplObjectStorage $replacements): array
    {
        $result = [];

        foreach ($rules as $rule) {
            $result[] = $this->resolveReplacement($rule, $replacements);
        }

        return $result;
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, RuleDefinition> $replacements
     */
    private function resolveReplacement(RuleDefinition $rule, \SplObjectStorage $replacements): RuleDefinition
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $visited */
        $visited = new \SplObjectStorage();

        // A rule may be replaced by the one that is replaced as well
        while ($replacements->offsetExists($rule) && !$visited->offsetExists($rule)) {
            $visited->offsetSet($rule);

            $rule = $replacements[$rule];
        }

        return $rule;
    }
}
