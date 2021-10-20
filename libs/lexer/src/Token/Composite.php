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
     * @param string $name
     * @param string $value
     * @param positive-int|0 $offset
     * @param array<string> $children
     */
    public function __construct(string $name, string $value, int $offset, array $children)
    {
        $this->children = $children;

        parent::__construct($name, $value, $offset);
    }

    /**
     * @param string $name
     * @param array<string> $groups
     * @param positive-int|0 $offset
     * @return static
     */
    public static function fromArray(string $name, array $groups, int $offset): self
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
    public function offsetExists($offset): bool
    {
        assert(is_int($offset) && $offset >= 0);

        return isset($this->getChildren()[$offset]);
    }

    /**
     * @param positive-int|0 $offset
     * @return TokenInterface|null
     */
    public function offsetGet($offset): ?TokenInterface
    {
        assert(is_int($offset) && $offset >= 0);

        return $this->getChildren()[$offset] ?? null;
    }

    /**
     * @param positive-int|0 $offset
     * @param TokenInterface $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        assert(is_int($offset) && $offset >= 0);
        assert($value instanceof TokenInterface);

        $this->getChildren()[$offset] = $value;
    }

    /**
     * @param positive-int|0 $offset
     * @return void
     */
    public function offsetUnset($offset): void
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
