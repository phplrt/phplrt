<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\Reducer\ReducerInterface;
use Phplrt\Parser\Context;

/**
 * @property-read list<RuleDefinition> $children Contains the rules the current
 *         one refers to.
 *
 * @phpstan-type ReducerType callable(Context, mixed): mixed
 */
abstract class RuleDefinition extends Definition
{
    /**
     * @var non-empty-string|null
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public ?string $name = null;

    /**
     * Contains {@see true} in case of the analysis may be started at the rule,
     * so it is kept on the compiled parser under its own identifier
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public bool $isEntrypoint = false;

    /**
     * Contains the reducer converting the rule into the node of the syntax
     * tree, or {@see null} in case of the rule is reduced to its children
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public ?ReducerInterface $reducer = null;

    /**
     * Contains the message reported in case of the rule cannot be recognized,
     * or {@see null} in case of the failure is described by the tokens that
     * could have been read instead.
     *
     * A placeholder of the message is written in braces, the way it is written
     * for a logger, and a brace of the message itself is written twice.
     *
     * @var non-empty-string|null
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public ?string $message = null;

    /**
     * Replaces every rule the current one refers to by the result of the given
     * callback.
     *
     * @param \Closure(RuleDefinition): RuleDefinition $replace
     */
    public function replaceChildren(\Closure $replace): void {}

    /**
     * Returns the rule along with the ones it refers to, directly or not, in
     * the order they are reached.
     *
     * @api
     *
     * @return non-empty-list<RuleDefinition>
     */
    public function collectRules(): array
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $visited */
        $visited = new \SplObjectStorage();

        $this->collectRule($visited);

        /** @var non-empty-list<RuleDefinition> */
        return \iterator_to_array($visited, false);
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, null> $visited
     */
    protected function collectRule(\SplObjectStorage $visited): void
    {
        if ($visited->offsetExists($this)) {
            return;
        }

        $visited->offsetSet($this);

        foreach ($this->getChildrenRuleDefinitions() as $child) {
            $child->collectRule($visited);
        }
    }

    /**
     * Returns the rules the current one refers to.
     *
     * @return list<RuleDefinition>
     */
    protected function getChildrenRuleDefinitions(): array
    {
        return [];
    }

    /**
     * Copies the rule along with the ones it refers to, directly or not.
     *
     * A rule may refer to itself and the very same rule may be referred to from
     * several places, so the copies made so far are carried along: the copy is
     * registered before its children are reached, and a rule already copied is
     * returned as is.
     *
     * @api
     *
     * @param \SplObjectStorage<RuleDefinition, RuleDefinition> $copies
     */
    public function clone(\SplObjectStorage $copies): self
    {
        if ($copies->offsetExists($this)) {
            return $copies[$this];
        }

        $copy = clone $this;

        $copies[$this] = $copy;

        $copy->replaceChildren(static fn(self $child): self => $child->clone($copies));

        return $copy;
    }

    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        ?string $name = null,
    ) {
        $this->name = $name;
    }

    /**
     * Updates whether the analysis may be started at the rule and returns
     * itself as the fluent interface.
     *
     * @api
     *
     * @return $this
     */
    public function setEntrypoint(bool $isEntrypoint = true): self
    {
        $this->isEntrypoint = $isEntrypoint;

        return $this;
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
     * A callback is executable, but cannot be dumped into the generated
     * parser, so a grammar meant to be generated defines its reducers using
     * {@see PhpCodeReducer} instead.
     *
     * @api
     *
     * @param ReducerInterface|ReducerType|null $reducer
     * @return $this
     */
    public function setReducer(ReducerInterface|callable|null $reducer): self
    {
        $this->reducer = match (true) {
            $reducer === null,
            $reducer instanceof ReducerInterface => $reducer,
            default => new CallableReducer($reducer),
        };

        return $this;
    }

    /**
     * Updates the message reported in case of the rule cannot be recognized
     * and returns itself as the fluent interface.
     *
     * @api
     *
     * @param non-empty-string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self
    {
        $this->message = $message;

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

    public function __get(string $property): mixed
    {
        return match ($property) {
            'children' => $this->getChildrenRuleDefinitions(),
            default => throw new \Error(\sprintf(
                'Undefined property %s::$%s',
                static::class,
                $property,
            )),
        };
    }
}
