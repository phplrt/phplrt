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
 * applied afterward.
 *
 * @property-read bool $isEmpty Contains {@see true} in case of no rule has been
 *                collected, or {@see false} instead.
 *
 *                Note: Starting with PHP 8.4, in the future, this annotation
 *                will be expressed as a full-fledged property.
 */
final class RuleReplacements
{
    /**
     * @var \SplObjectStorage<RuleDefinition, RuleDefinition>
     */
    private readonly \SplObjectStorage $replacements;

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
        if ($this->replacements->count() === 0) {
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

    public function __get(string $property): mixed
    {
        return match ($property) {
            'isEmpty' => $this->replacements->count() === 0,
            default => throw new \Error(\sprintf('Undefined property %s::$%s', static::class, $property)),
        };
    }
}
