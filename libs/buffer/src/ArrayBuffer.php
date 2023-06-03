<?php

declare(strict_types=1);

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

class ArrayBuffer extends Buffer
{
    /**
     * @var array<int<0, max>, TokenInterface>
     */
    private array $buffer;

    /**
     * @var int<0, max>
     */
    private int $size;

    /**
     * @param iterable<int<0, max>, TokenInterface> $stream
     */
    public function __construct(iterable $stream)
    {
        $this->buffer = $this->iterableToArray($stream);
        $this->size = \count($this->buffer);

        if (\count($this->buffer)) {
            $this->initial = $this->current = \array_key_first($this->buffer);
        }
    }

    /**
     * @param iterable<int<0, max>, TokenInterface> $tokens
     * @return array<int<0, max>, TokenInterface>
     */
    private function iterableToArray(iterable $tokens): array
    {
        if ($tokens instanceof \Traversable) {
            return \iterator_to_array($tokens, false);
        }

        return $tokens;
    }

    /**
     * {@inheritDoc}
     */
    public function seek($offset): void
    {
        if ($offset < $this->initial) {
            throw new \OutOfRangeException(
                \sprintf(self::ERROR_STREAM_POSITION_TO_LOW, $offset, $this->tokenToString($this->current()))
            );
        }

        if ($offset > ($last = \array_key_last($this->buffer))) {
            throw new \OutOfRangeException(
                \sprintf(self::ERROR_STREAM_POSITION_EXCEED, $offset, (int)$last)
            );
        }

        parent::seek($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function current(): TokenInterface
    {
        return $this->currentFrom($this->buffer);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        if ($this->current < $this->size) {
            ++$this->current;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return $this->current < $this->size;
    }
}
