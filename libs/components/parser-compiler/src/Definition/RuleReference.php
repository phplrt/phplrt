<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

/**
 * Refers to another rule of the grammar, addressed either by its definition or
 * by its name.
 *
 * A reference is replaced by the rule it points at while the parser is being
 * compiled, so the grammar itself never contains one.
 */
final class RuleReference extends RuleDefinition
{
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

    protected function printValue(): string
    {
        if (\is_string($this->target)) {
            return $this->target;
        }

        return $this->target->printReference();
    }
}
