<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

use Phplrt\Parser\Context;

/**
 * @phpstan-type ReducerType callable(Context, mixed): mixed
 */
abstract class RuleDefinition extends Definition
{
    /**
     * @var non-empty-string|null
     */
    public private(set) ?string $name = null;

    /**
     * Contains the callback converting the rule into the node of the syntax
     * tree, or {@see null} in case of the rule is reduced to its children
     *
     * @var (ReducerType&\Closure)|null
     */
    public private(set) ?\Closure $reducer = null;

    /**
     * Contains the rules the current one refers to
     *
     * @var list<RuleDefinition>
     */
    public array $children { get => []; }

    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        ?string $name = null,
    ) {
        $this->name = $name;
    }

    /**
     * Updates the rule name of the current definition and returns
     * itself as the fluent interface.
     *
     * @api
     *
     * @param non-empty-string|null $name
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @api
     *
     * @return $this
     */
    public function setAnonymous(): self
    {
        return $this->setName(null);
    }

    /**
     * Updates the reducer of the current definition and returns itself as the
     * fluent interface.
     *
     * @api
     *
     * @param ReducerType|null $reducer
     * @return $this
     */
    public function setReducer(?callable $reducer): self
    {
        $this->reducer = $reducer === null ? null : $reducer(...);

        return $this;
    }

    /**
     * Returns the rule as it is referred to by another one.
     *
     * @return non-empty-string
     */
    public function printReference(): string
    {
        return $this->name ?? $this->printValue();
    }

    /**
     * @return non-empty-string
     */
    abstract protected function printValue(): string;

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        if ($this->name === null) {
            return $this->printValue();
        }

        return \sprintf('%s = %s', $this->name, $this->printValue());
    }
}
