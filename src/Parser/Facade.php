<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Rule\Concatenation;
use Phplrt\Parser\Rule\Lexeme;
use Phplrt\Parser\Rule\RuleInterface;

/**
 * Trait Facade
 */
trait Facade
{
    /**
     * @var array|int[]
     */
    private $names = [];

    /**
     * @var array|\Closure[]
     */
    private $reducers = [];

    /**
     * @param \Closure $grammar
     * @return Facade|$this
     */
    public function extend(\Closure $grammar): self
    {
        $stream = $grammar($this);

        if (! $stream instanceof \Generator) {
            $error = 'Closure must return an instance of Generator, but %s given';
            throw new \InvalidArgumentException(\sprintf($error, \gettype($stream)));
        }

        while ($stream->valid()) {
            [$name, $value] = [$this->map($stream->key()), $stream->current()];

            if (! $value instanceof RuleInterface) {
                $error = 'Generator value should be an instance of %s but %s given';
                throw new \InvalidArgumentException(\sprintf($error, RuleInterface::class, \gettype($stream)));
            }

            $this->rules[$name] = $value;

            $stream->send($name);
        }

        return $this;
    }

    /**
     * @param string|int $rule
     * @param \Closure $then
     * @return Facade
     */
    public function when($rule, \Closure $then): self
    {
        $index = (($found = \array_search($rule, $this->names, true)) === false) ? $rule : $found;

        $this->reducers[$index] = $then;

        return $this;
    }

    /**
     * @param string|int $rule
     * @return Facade
     */
    public function startsAt($rule): self
    {
        $this->initial = $this->map($rule);

        return $this;
    }

    /**
     * @param $value
     * @return int
     */
    private function map($value): int
    {
        $index = \count($this->rules) ? \array_key_last($this->rules) + 1 : 0;

        switch (true) {
            case \is_string($value):
                return $this->names[$value] ?? $this->names[$value] = $index;

            case \is_int($value):
                return $value;

            case $value === null:
                return $index;

            default:
                $error = 'Rule name must be a string or integer, but %s given';
                throw new \InvalidArgumentException(\sprintf($error, \gettype($value)));
        }
    }

    /**
     * @param iterable|int[]|string[] $values
     * @return array|int[]
     */
    private function mapAll(iterable $values): array
    {
        $result = [];

        foreach ($values as $value) {
            $result[] = $this->map($value);
        }

        return $result;
    }

    /**
     * @param iterable $sequence
     * @return Concatenation
     */
    public function concat(iterable $sequence): Concatenation
    {
        return new Concatenation($this->mapAll($sequence));
    }

    /**
     * @param string $name
     * @return Lexeme
     */
    public function token(string $name): Lexeme
    {
        return new Lexeme($name, true);
    }

    /**
     * @param string $name
     * @return Lexeme
     */
    public function skip(string $name): Lexeme
    {
        return new Lexeme($name, false);
    }
}
