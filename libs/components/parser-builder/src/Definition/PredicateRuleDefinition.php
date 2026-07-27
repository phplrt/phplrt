<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

/**
 * Looks at what comes next without reading it.
 *
 * The rule is recognized against the input the very same way as any other, but
 * whatever it has recognized is thrown away afterwards: neither the input nor
 * the result moves forward, so the only thing left is the answer.
 */
final class PredicateRuleDefinition extends ProductionRuleDefinition
{
    public private(set) RuleDefinition $rule;

    /**
     * Contains {@see true} in case of the rule must be recognized at this
     * position, or {@see false} in case of it must not
     */
    public private(set) bool $isExpected;

    public array $children {
        get => [$this->rule];
    }

    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        RuleDefinition $rule,
        bool $isExpected = true,
        ?string $name = null,
    ) {
        $this->rule = $rule;
        $this->isExpected = $isExpected;

        parent::__construct($name);
    }

    /**
     * Updates the rule of the current definition and returns itself as the
     * fluent interface.
     *
     * @api
     *
     * @return $this
     */
    public function setRule(RuleDefinition $rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Requires the rule to be recognized at this position.
     *
     * @api
     *
     * @return $this
     */
    public function expect(): self
    {
        return $this->setExpected();
    }

    /**
     * Requires the rule NOT to be recognized at this position.
     *
     * @api
     *
     * @return $this
     */
    public function reject(): self
    {
        return $this->setExpected(false);
    }

    /**
     * @api
     *
     * @return $this
     */
    public function setExpected(bool $expected = true): self
    {
        $this->isExpected = $expected;

        return $this;
    }

    public function replaceChildren(\Closure $replace): void
    {
        $this->rule = $replace($this->rule);
    }

    protected function printValue(): string
    {
        return ($this->isExpected ? '&' : '!') . $this->rule->printReference();
    }
}
