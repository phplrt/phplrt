<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Grammar;

use Phplrt\Contracts\Grammar\RuleInterface;

/**
 * @psalm-type RuleId = non-empty-string|int
 * @psalm-type InitializerExpression = \Closure(Builder): \Generator<RuleId, RuleInterface, RuleId, mixed>
 * @template-implements \IteratorAggregate<RuleId, RuleInterface>
 */
class Builder implements \IteratorAggregate
{
    /**
     * @var string
     */
    private const ERROR_INVALID_PAYLOAD = 'Closure should return an instance of \Generator';

    /**
     * @var array<int|non-empty-string, RuleInterface>
     */
    private array $grammar = [];

    /**
     * Builder constructor.
     *
     * @param InitializerExpression|null $generator
     */
    public function __construct(\Closure $generator = null)
    {
        if ($generator) {
            $this->extend($generator);
        }
    }

    /**
     * <code>
     *  $builder->extend(static function (): \Generator {
     *      // Example with named rule
     *      $name = yield 'RuleName' => new Concatenation([1, 2, 3]);
     *
     *      // Example with anonymous rule
     *      yield new Concatenation([1, 2, 3]);
     *  });
     * </code>
     *
     * @param InitializerExpression $rules
     * @return $this
     */
    public function extend(\Closure $rules): self
    {
        $generator = $this->read($rules);

        while ($generator->valid()) {
            /** @psalm-suppress MixedAssignment */
            [$key, $value] = [$generator->key(), $generator->current()];

            switch (true) {
                case \is_string($key) && $value instanceof RuleInterface:
                    /** @psalm-suppress ArgumentTypeCoercion */
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
     * @param RuleId ...$of
     * @return Concatenation
     */
    public function concat(string|int ...$of): Concatenation
    {
        return new Concatenation($of);
    }

    /**
     * @param RuleId ...$of
     * @return Alternation
     */
    public function any(string|int ...$of): Alternation
    {
        return new Alternation($of);
    }

    /**
     * @param RuleId $name
     * @param bool $keep
     * @return Lexeme
     */
    public function token(string|int $name, bool $keep = true): Lexeme
    {
        return new Lexeme($name, $keep);
    }

    /**
     * @param RuleId ...$of
     * @return Optional
     */
    public function maybe(string|int ...$of): Optional
    {
        return new Optional($this->unwrap($of));
    }

    /**
     * @param array<RuleId> $args
     * @return non-empty-string|int
     * @psalm-suppress NullableReturnStatement
     * @psalm-suppress InvalidNullableReturnType
     */
    private function unwrap(array $args): string|int
    {
        if (\count($args) > 1) {
            return $this->add($this->concat(...$args));
        }

        return \reset($args);
    }

    /**
     * @param RuleId ...$of
     * @return Repetition
     */
    public function repeat(string|int ...$of): Repetition
    {
        return new Repetition($this->unwrap($of));
    }

    /**
     * @param InitializerExpression $rules
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
     * @param RuleId|null $id
     * @return non-empty-string|int
     */
    public function add(RuleInterface $rule, int|string $id = null): int|string
    {
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
