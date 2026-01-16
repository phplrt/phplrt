<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

class Composite extends Token implements CompositeTokenInterface
{
    /**
     * @param array-key $name
     * @param int<0, max> $offset
     * @param array<int, TokenInterface> $children
     */
    public function __construct(
        string|int $name,
        string $value,
        int $offset,
        private array $children,
    ) {
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

    /**
     * @param int $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        \assert(\is_int($offset));

        return isset($this->children[$offset]);
    }

    /**
     * @param int $offset
     */
    public function offsetGet(mixed $offset): ?TokenInterface
    {
        \assert(\is_int($offset));

        return $this->children[$offset] ?? null;
    }

    /**
     * @param int $offset
     * @param TokenInterface $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        \assert(\is_int($offset));
        \assert($value instanceof TokenInterface);

        $this->children[$offset] = $value;
    }

    /**
     * @param int $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        \assert(\is_int($offset));

        unset($this->children[$offset]);
    }

    public function count(): int
    {
        return \count($this->children);
    }
}
