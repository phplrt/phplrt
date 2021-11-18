<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

final class Composite extends Token implements CompositeTokenInterface
{
    /**
     * @var array<string>
     */
    private array $children;

    /**
     * @var array<TokenInterface>
     */
    private array $initialized = [];

    /**
     * @param string|int $name
     * @param string $value
     * @param positive-int|0 $offset
     * @param array<string> $children
     */
    public function __construct(string|int $name, string $value, int $offset, array $children)
    {
        $this->children = $children;

        parent::__construct($name, $value, $offset);
    }

    /**
     * @param string|int $name
     * @param array<string> $groups
     * @param positive-int|0 $offset
     * @return static
     */
    public static function fromArray(string|int $name, array $groups, int $offset): self
    {
        /** @var string $body */
        $body = \array_shift($groups);

        return new self($name, $body, $offset, $groups);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return \array_merge(parent::jsonSerialize(), [
            'children' => $this->getChildren(),
        ]);
    }

    /**
     * @return array<TokenInterface>
     */
    private function getChildren(): array
    {
        if ($this->initialized === []) {
            foreach ($this->children as $child) {
                $this->initialized[] = new Token($this->name, $child, $this->offset);
            }
        }

        return $this->initialized;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->getChildren());
    }

    /**
     * @param positive-int|0 $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        assert(is_int($offset) && $offset >= 0);

        return isset($this->getChildren()[$offset]);
    }

    /**
     * @param positive-int|0 $offset
     * @return TokenInterface|null
     */
    public function offsetGet(mixed $offset): ?TokenInterface
    {
        assert(is_int($offset) && $offset >= 0);

        return $this->getChildren()[$offset] ?? null;
    }

    /**
     * @param positive-int|0 $offset
     * @param string $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert(is_int($offset) && $offset >= 0);
        assert(is_string($value));

        $this->initialized = [];
        $this->children[$offset] = $value;
    }

    /**
     * @param positive-int|0 $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        assert(is_int($offset) && $offset >= 0);

        unset($this->getChildren()[$offset]);
    }

    /**
     * @return positive-int
     * @psalm-suppress InvalidReturnType (Count of children tokens greater than 0)
     */
    public function count(): int
    {
        /**
         * @psalm-suppress InvalidReturnStatement (Count of children tokens greater than 0)
         */
        return \count($this->children);
    }
}
