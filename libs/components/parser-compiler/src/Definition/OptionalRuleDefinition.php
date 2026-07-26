<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

/**
 * Recognizes the given rule, if the input matches it.
 */
final class OptionalRuleDefinition extends ProductionRuleDefinition
{
    public private(set) RuleDefinition $rule;

    public array $children {
        get => [$this->rule];
    }

    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        RuleDefinition $rule,
        ?string $name = null,
    ) {
        $this->rule = $rule;

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

    protected function printValue(): string
    {
        return $this->rule->printReference() . '?';
    }
}
