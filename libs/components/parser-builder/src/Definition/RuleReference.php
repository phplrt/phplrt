<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

/**
 * Refers to another rule of the grammar, addressed either by its definition or
 * by its name.
 *
 * A reference is replaced by the rule it points at while the parser is being
 * compiled, so the grammar itself never contains one.
 */
final class RuleReference extends RuleDefinition
{
    public array $children {
        get => \is_string($this->target) ? [] : [$this->target];
    }

    /**
     * @param RuleDefinition|non-empty-string $target
     */
    public function __construct(
        /**
         * Contains the rule the reference points at, or the name of that rule
         *
         * @var RuleDefinition|non-empty-string
         */
        public private(set) RuleDefinition|string $target,
    ) {
        parent::__construct();
    }

    /**
     * Updates the rule the reference points at and returns itself as the
     * fluent interface.
     *
     * @api
     *
     * @param RuleDefinition|non-empty-string $target
     * @return $this
     */
    public function setTarget(RuleDefinition|string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function replaceChildren(\Closure $replace): void
    {
        if (\is_string($this->target)) {
            return;
        }

        $this->target = $replace($this->target);
    }

    protected function printValue(): string
    {
        if (\is_string($this->target)) {
            return $this->target;
        }

        return $this->target->printReference();
    }
}
