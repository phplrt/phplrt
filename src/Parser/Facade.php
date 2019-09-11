<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Rule\Lexeme;
use Phplrt\Parser\Rule\Optional;
use Phplrt\Parser\Rule\Repetition;
use Phplrt\Parser\Rule\Alternation;
use Phplrt\Parser\Rule\Concatenation;
use Phplrt\Parser\Rule\RuleInterface;

/**
 * Trait Facade
 */
trait Facade
{
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
            [$name, $value] = [$stream->key(), $stream->current()];

            if ($value instanceof RuleInterface) {
                $stream->send($this->with($value, $name));

                continue;
            }

            $stream->send($value);
        }

        return $this;
    }

    /**
     * @param RuleInterface $rule
     * @param null $name
     * @return int|string
     */
    public function with(RuleInterface $rule, $name = null)
    {
        if ($name === null) {
            $this->rules[] = $rule;

            return \array_key_last($this->rules);
        }

        $this->rules[$name] = $rule;

        return $name;
    }

    /**
     * @param int|string|int[]|string[] $of
     * @param int $from
     * @param float $to
     * @return Repetition
     */
    public function some($of, int $from = 0, float $to = \INF): Repetition
    {
        $of = $this->resolve(\is_array($of) ? $this->all($of) : $of);

        return new Repetition($of, $from, $to);
    }

    /**
     * @param mixed $rule
     * @return array|int|string
     */
    private function resolve($rule)
    {
        switch (true) {
            case \is_array($rule):
                return \array_map([$this, 'resolve'], $rule);

            case $rule instanceof RuleInterface:
                return $this->with($rule);

            default:
                return $rule;
        }
    }

    /**
     * @param int|string|int[]|string[] $of
     * @return Concatenation
     */
    public function all($of): Concatenation
    {
        $of = $this->resolve(\is_array($of) ? $of : [$of]);

        return new Concatenation($of);
    }

    /**
     * @param int|string|int[]|string[] $of
     * @return Alternation
     */
    public function any($of): Alternation
    {
        $of = $this->resolve(\is_array($of) ? $of : [$of]);

        return new Alternation($of);
    }

    /**
     * @param int|string|int[]|string[] $of
     * @return Optional
     */
    public function maybe($of): Optional
    {
        $of = $this->resolve(\is_array($of) ? $this->all($of) : $of);

        return new Optional($of);
    }

    /**
     * @param string $token
     * @param string ...$tokens
     * @return Concatenation|Lexeme
     */
    public function is(string $token, string ...$tokens)
    {
        $tokens = \array_merge([$token], $tokens);

        if (\count($tokens) === 1) {
            return new Lexeme(\reset($tokens), true);
        }

        return $this->all(\array_map([$this, 'is'], $tokens));
    }

    /**
     * @param \Closure $builder
     * @return Facade|$this
     */
    public function where(\Closure $builder): self
    {
        $builder($this->builder);

        return $this;
    }

    /**
     * @param string $name
     * @return Lexeme
     */
    public function like(string $name): Lexeme
    {
        return new Lexeme($name, false);
    }
}
