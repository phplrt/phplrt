<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * @template-implements \IteratorAggregate<array-key, RuleInterface>
 */
class Builder implements \IteratorAggregate
{
    private const ERROR_INVALID_PAYLOAD = 'Closure should return an instance of \Generator';

    /**
     * @var array<array-key, RuleInterface>
     */
    private array $grammar = [];

    /**
     * @param \Closure():\Generator|null $generator
     */
    public function __construct(?\Closure $generator = null)
    {
        if ($generator !== null) {
            $this->extend($generator);
        }
    }

    /**
     * ```
     *  $builder->extend(function () {
     *      // Example with named rule
     *      $name = yield 'RuleName' => new Concatenation([1, 2, 3])
     *  });
     * ```
     *
     * @param \Closure():\Generator $rules
     */
    public function extend(\Closure $rules): self
    {
        $generator = $this->read($rules);

        while ($generator->valid()) {
            $key = $generator->key();
            $value = $generator->current();

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
     * @param array-key ...$of
     */
    public function concat(...$of): Concatenation
    {
        return new Concatenation($of);
    }

    /**
     * @param array-key ...$of
     */
    public function any(...$of): Alternation
    {
        return new Alternation($of);
    }

    /**
     * @param non-empty-string $named
     */
    public function token(string $named, bool $keep = true): Lexeme
    {
        return new Lexeme($named, $keep);
    }

    /**
     * @param array-key $of
     */
    public function maybe(...$of): Optional
    {
        return new Optional($this->unwrap($of));
    }

    /**
     * @param non-empty-list<array-key> $args
     *
     * @return array-key
     */
    private function unwrap(array $args)
    {
        if (\count($args) > 1) {
            return $this->add($this->concat(...$args));
        }

        return \reset($args);
    }

    /**
     * @param array-key ...$of
     */
    public function repeat(...$of): Repetition
    {
        return new Repetition($this->unwrap($of));
    }

    private function read(\Closure $rules): \Generator
    {
        $generator = $rules($this);

        if ($generator instanceof \Generator) {
            return $generator;
        }

        throw new \InvalidArgumentException(self::ERROR_INVALID_PAYLOAD);
    }

    /**
     * @param array-key|null $id
     *
     * @return array-key
     */
    public function add(RuleInterface $rule, string|int|null $id = null): int|string
    {
        if ($id === null) {
            $this->grammar[] = $rule;

            return \array_key_last($this->grammar);
        }

        $this->grammar[$id] = $rule;

        return $id;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->grammar);
    }
}
