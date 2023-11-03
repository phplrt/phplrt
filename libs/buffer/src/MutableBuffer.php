<?php

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * This class is decorator for BufferInterface.
 * It adds methods for direct read/write elements of buffer.
 */
class MutableBuffer implements BufferInterface
{
    private BufferInterface $parent;

    /**
     * @var array<int<0, max>, TokenInterface>
     */
    private array $overrides = [];

    public function __construct(BufferInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param int<0, max> $offset
     */
    public function set(int $offset, TokenInterface $token): void
    {
        $this->overrides[$offset] = $token;
    }

    /**
     * @param int<0, max> $offset
     */
    public function get(int $offset): TokenInterface
    {
        return $this->overrides[$offset] ?? $this->poll($offset);
    }

    /**
     * @param int<0, max> $offset
     */
    private function poll(int $offset): TokenInterface
    {
        $previous = $this->parent->key();

        try {
            $this->parent->seek($offset);

            return $this->parent->current();
        } finally {
            $this->parent->seek($previous);
        }
    }

    public function seek($offset): void
    {
        $this->parent->seek($offset);
    }

    public function current(): TokenInterface
    {
        return $this->overrides[$this->parent->key()] ?? $this->parent->current();
    }

    public function key(): int
    {
        return $this->parent->key();
    }

    public function valid(): bool
    {
        return $this->parent->valid();
    }

    public function rewind(): void
    {
        $this->parent->rewind();
    }

    public function next(): void
    {
        $this->parent->next();
    }
}
