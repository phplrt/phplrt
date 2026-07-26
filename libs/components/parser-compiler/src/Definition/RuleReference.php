<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

/**
 * Refers to the rule with the given name.
 *
 * A reference is replaced by the rule it points at while the parser is being
 * compiled, so the grammar itself never contains one.
 */
final class RuleReference extends RuleDefinition
{
    /**
     * @param non-empty-string $target
     */
    public function __construct(
        /**
         * Contains the name of the rule the reference points at
         *
         * @var non-empty-string
         */
        public private(set) string $target,
    ) {
        parent::__construct();
    }

    /**
     * Updates the name of the rule the reference points at and returns itself
     * as the fluent interface.
     *
     * @api
     *
     * @param non-empty-string $target
     * @return $this
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    protected function printValue(): string
    {
        return $this->target;
    }
}
