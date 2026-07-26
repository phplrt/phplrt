<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

/**
 * Recognizes all the given rules, one after another.
 */
final class ConcatenationRuleDefinition extends ProductionRuleDefinition
{
    use HasChildRuleDefinitions;

    public array $children {
        get => $this->rules;
    }

    /**
     * @param list<RuleDefinition> $rules
     * @param non-empty-string|null $name
     */
    public function __construct(
        array $rules = [],
        ?string $name = null,
    ) {
        $this->rules = $rules;

        parent::__construct($name);
    }

    protected function printValue(): string
    {
        if ($this->rules === []) {
            return '()';
        }

        $references = [];

        foreach ($this->rules as $rule) {
            $references[] = $rule->printReference();
        }

        return \implode(' ', $references);
    }
}
