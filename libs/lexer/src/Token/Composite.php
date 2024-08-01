<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

class Composite extends Token implements CompositeTokenInterface
{
    /**
     * @var array<int, TokenInterface>
     */
    private array $children = [];

    /**
     * @param array-key $name
     * @param int<0, max> $offset
     * @param array<int, TokenInterface> $children
     */
    public function __construct(int|string $name, string $value, int $offset, array $children)
    {
        $this->children = $children;

        parent::__construct($name, $value, $offset);
    }

    /**
     * @param non-empty-array<int, TokenInterface> $tokens
     */
    public static function fromArray(array $tokens): self
    {
        \assert($tokens !== []);

        $first = \array_shift($tokens);

        return new self($first->getName(), $first->getValue(), $first->getOffset(), $tokens);
    }

    public function jsonSerialize(): array
    {
        return \array_merge(parent::jsonSerialize(), [
            'children' => $this->children,
        ]);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function offsetExists(mixed $offset): bool
    {
        \assert(\is_int($offset));

        return isset($this->children[$offset]);
    }

    public function offsetGet(mixed $offset): ?TokenInterface
    {
        \assert(\is_int($offset));

        return $this->children[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        \assert(\is_int($offset));
        \assert($value instanceof TokenInterface);

        $this->children[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        \assert(\is_int($offset));

        unset($this->children[$offset]);
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return \count($this->children);
    }
}
