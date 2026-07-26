<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

/**
 * @phpstan-sealed AlternationRuleDefinition|ConcatenationRuleDefinition|OptionalRuleDefinition|RepetitionRuleDefinition
 */
abstract class ProductionRuleDefinition extends RuleDefinition
{
    /**
     * A production may refer to itself, so it is printed by its name (or by
     * a placeholder in case of an anonymous rule) instead of its content.
     */
    public function printReference(): string
    {
        return $this->name ?? '(...)';
    }
}
