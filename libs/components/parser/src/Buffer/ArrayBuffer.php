<?php

declare(strict_types=1);

namespace Phplrt\Parser\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Exception\OutOfRangeException;

final class ArrayBuffer implements BufferInterface
{
    /**
     * @var list<TokenInterface>
     */
    private readonly array $tokens;

    /**
     * @var int<0, max>
     */
    private readonly int $size;

    /**
     * @var int<0, max>
     */
    private int $offset = 0;

    /**
     * @param iterable<mixed, TokenInterface> $tokens
     */
    public function __construct(iterable $tokens)
    {
        $this->tokens = \iterator_to_array($tokens, false);
        $this->size = \count($this->tokens);
    }

    public function seek(int $offset): void
    {
        \assert($offset >= 0, OutOfRangeException::becausePositionOutOfRange($offset, $this->size));

        if ($offset > $this->size) {
            throw OutOfRangeException::becausePositionOutOfRange($offset, $this->size);
        }

        $this->offset = $offset;
    }

    public function current(): TokenInterface
    {
        return $this->tokens[$this->offset]
            ?? $this->tokens[$this->size - 1];
    }

    public function key(): int
    {
        $offset = $this->offset;

        if ($offset >= $this->size) {
            return $this->size - 1;
        }

        return $offset;
    }

    public function valid(): bool
    {
        return $this->offset < $this->size;
    }

    public function rewind(): void
    {
        $this->offset = 0;
    }

    public function next(): void
    {
        if ($this->offset > $this->size) {
            return;
        }

        ++$this->offset;
    }
}
