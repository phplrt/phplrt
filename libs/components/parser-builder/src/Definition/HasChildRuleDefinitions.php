<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

/**
 * @internal this is an internal library trait, please do not use it in your code
 * @psalm-internal Phplrt\Parser\Builder
 */
trait HasChildRuleDefinitions
{
    /**
     * @var list<RuleDefinition>
     */
    public private(set) array $rules = [];

    /**
     * Updates the rules of the current definition and returns itself as the
     * fluent interface.
     *
     * @api
     *
     * @param list<RuleDefinition> $rules
     * @return $this
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Appends the rule to the current definition and returns itself as the
     * fluent interface.
     *
     * A rule may refer to itself, so the reference can be added later, after
     * the definition has already been created.
     *
     * @api
     *
     * @return $this
     */
    public function addRule(RuleDefinition $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @param \Closure(RuleDefinition): RuleDefinition $replace
     */
    public function replaceChildren(\Closure $replace): void
    {
        $result = [];

        foreach ($this->rules as $rule) {
            $result[] = $replace($rule);
        }

        $this->rules = $result;
    }
}
