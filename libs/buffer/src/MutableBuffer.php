<?php

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * This class is decorator for BufferInterface.
 * It adds methods for direct read/write elements of buffer.
 */
class MutableBuffer implements BufferInterface
{
    /**
     * @var BufferInterface
     */
    private BufferInterface $parent;

    /**
     * @var array
     */
    private array $overrides = [];

    /**
     * @param BufferInterface $parentBuffer
     */
    public function __construct(BufferInterface $parentBuffer)
    {
        $this->parent = $parentBuffer;
    }

    /**
     * @param int $offset
     * @param TokenInterface $token
     * @return void
     */
    public function set(int $offset, TokenInterface $token): void
    {
        $this->overrides[$offset] = $token;
    }

    /**
     * @param int $offset
     * @return TokenInterface
     */
    public function get(int $offset): TokenInterface
    {
        return $this->overrides[$offset] ?? $this->poll($offset);
    }

    /**
     * @param int $offset
     * @return TokenInterface
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

    /**
     * @param $offset
     * @return void
     */
    public function seek($offset): void
    {
        $this->parent->seek($offset);
    }

    /**
     * @return TokenInterface
     */
    public function current(): TokenInterface
    {
        return $this->overrides[$this->parent->key()] ?? $this->parent->current();
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->parent->key();
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->parent->valid();
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->parent->rewind();
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->parent->next();
    }
}
