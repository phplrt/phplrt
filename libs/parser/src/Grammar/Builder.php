<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * @template-implements \IteratorAggregate<int<0, max>|non-empty-string, RuleInterface>
 */
class Builder implements \IteratorAggregate
{
    /**
     * @var string
     */
    private const ERROR_INVALID_PAYLOAD = 'Closure should return an instance of \Generator';

    /**
     * @var array<int<0, max>|non-empty-string, RuleInterface>
     */
    private array $grammar = [];

    /**
     * @param \Closure|null $generator
     */
    public function __construct(\Closure $generator = null)
    {
        if ($generator) {
            $this->extend($generator);
        }
    }

    /**
     * <code>
     *  $builder->extend(function () {
     *      // Example with named rule
     *      $name = yield 'RuleName' => new Concatenation([1, 2, 3])
     *
     *      // Example with anonymous rule
     *      yield
     *  });
     * </code>
     *
     * @param \Closure $rules
     * @return $this
     */
    public function extend(\Closure $rules): self
    {
        $generator = $this->read($rules);

        while ($generator->valid()) {
            [$key, $value] = [$generator->key(), $generator->current()];

            switch (true) {
                case \is_string($key) && $value instanceof RuleInterface:
                    $generator->send($this->add($value, $key));
                    continue 2;

                case $value instanceof RuleInterface:
                    $generator->send($this->add($value));
                    continue 2;
            }

            $generator->send($value);
        }

        return $this;
    }

    /**
     * @param int|string ...$of
     * @return Concatenation
     */
    public function concat(...$of): Concatenation
    {
        return new Concatenation($of);
    }

    /**
     * @param int|string ...$of
     * @return Alternation
     */
    public function any(...$of): Alternation
    {
        return new Alternation($of);
    }

    /**
     * @param string $named
     * @param bool $keep
     * @return Lexeme
     */
    public function token(string $named, bool $keep = true): Lexeme
    {
        return new Lexeme($named, $keep);
    }

    /**
     * @param string|int $of
     * @return Optional
     */
    public function maybe(...$of): Optional
    {
        return new Optional($this->unwrap($of));
    }

    /**
     * @param array|string[]|int[] $args
     * @return int|string
     */
    private function unwrap(array $args)
    {
        if (\count($args) > 1) {
            return $this->add($this->concat(...$args));
        }

        return \reset($args);
    }

    /**
     * @param mixed ...$of
     * @return Repetition
     */
    public function repeat(...$of): Repetition
    {
        return new Repetition($this->unwrap($of));
    }

    /**
     * @param \Closure $rules
     * @return \Generator
     */
    private function read(\Closure $rules): \Generator
    {
        $generator = $rules($this);

        if ($generator instanceof \Generator) {
            return $generator;
        }

        throw new \InvalidArgumentException(\sprintf(self::ERROR_INVALID_PAYLOAD));
    }

    /**
     * @param RuleInterface $rule
     * @param int|string $id
     * @return int|string|null
     */
    public function add(RuleInterface $rule, $id = null)
    {
        \assert($id === null || \is_int($id) || \is_string($id)); /** @phpstan-ignore-line */

        if ($id === null) {
            $this->grammar[] = $rule;

            return \array_key_last($this->grammar);
        }

        $this->grammar[$id] = $rule;

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->grammar);
    }
}
