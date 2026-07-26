<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

/**
 * Recognizes the given rule as many times as the input matches it.
 */
final class RepetitionRuleDefinition extends ProductionRuleDefinition implements
    SequenceRuleDefinitionInterface
{
    public private(set) RuleDefinition $rule;

    /**
     * @var int<0, max>
     */
    public private(set) int $min;

    /**
     * @var int<0, max>|float
     */
    public private(set) int|float $max;

    public array $children {
        get => [$this->rule];
    }

    /**
     * @param int<0, max> $min
     * @param int<0, max>|float $max
     * @param non-empty-string|null $name
     */
    public function __construct(
        RuleDefinition $rule,
        int $min = 0,
        int|float $max = \INF,
        ?string $name = null,
    ) {
        $this->rule = $rule;
        $this->min = $min;
        $this->max = $max;

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
     * @api
     *
     * @param int<0, max> $min
     * @return $this
     */
    public function setMin(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    /**
     * @api
     *
     * @param int<0, max>|float $max
     * @return $this
     */
    public function setMax(int|float $max): self
    {
        $this->max = $max;

        return $this;
    }

    protected function printValue(): string
    {
        $reference = $this->rule->printReference();

        if (!\is_infinite($this->max)) {
            return \sprintf('%s{%d,%s}', $reference, $this->min, $this->max);
        }

        return match ($this->min) {
            0 => $reference . '*',
            1 => $reference . '+',
            default => \sprintf('%s{%d,}', $reference, $this->min),
        };
    }
}
