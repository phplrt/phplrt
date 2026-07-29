<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Parser\Builder\Definition\RuleDefinition;

/**
 * Collects the rules that are equivalent to another one and swaps them in the
 * grammar all at once.
 *
 * Rewriting the grammar while it is being walked would make a pass depend on
 * the order the rules are reached, so the replacements are gathered first and
 * applied afterwards.
 */
final class RuleReplacements
{
    /**
     * @var \SplObjectStorage<RuleDefinition, RuleDefinition>
     */
    private readonly \SplObjectStorage $replacements;

    public bool $isEmpty {
        get => $this->replacements->count() === 0;
    }

    public function __construct()
    {
        $this->replacements = new \SplObjectStorage();
    }

    /**
     * @return $this
     */
    public function replace(RuleDefinition $rule, RuleDefinition $replacement): self
    {
        $this->replacements->offsetSet($rule, $replacement);

        return $this;
    }

    /**
     * Swaps every occurrence of the collected rules, including the initial one.
     */
    public function applyTo(ParserBuildingContext $context): void
    {
        if ($this->isEmpty) {
            return;
        }

        foreach ($context->rules as $rule) {
            $rule->replaceChildren($this->resolve(...));
        }

        $initial = $context->initial;

        if ($initial !== null) {
            $context->initial = $this->resolve($initial);
        }

        // A replaced rule is no longer a part of the grammar
        $context->rules = $context->initial?->collectRules() ?? [];
    }

    /**
     * Returns the rule the given one is replaced by, or the rule itself in case
     * of it is not replaced.
     */
    public function resolve(RuleDefinition $rule): RuleDefinition
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $visited */
        $visited = new \SplObjectStorage();

        // A rule may be replaced by the one that is replaced as well
        while ($this->replacements->offsetExists($rule) && !$visited->offsetExists($rule)) {
            $visited->offsetSet($rule);

            $rule = $this->replacements[$rule];
        }

        return $rule;
    }
}
