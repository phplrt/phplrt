<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

/**
 * Requires the surrounding tokens to be written one right after another.
 */
final class AdjacencyRuleDefinition extends RuleDefinition
{
    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        /**
         * Contains {@see true} in case of the tokens must be written with nothing
         * in between, or {@see false} in case of they must not
         *
         * @phpstan-readonly-allow-private-mutation
         */
        public bool $isExpected = true,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    /**
     * Requires the tokens around the rule to be written with nothing in
     * between.
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
     * Requires something to be written between the tokens around the rule.
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

    protected function printValue(): string
    {
        return $this->isExpected ? '~' : '!~';
    }
}
