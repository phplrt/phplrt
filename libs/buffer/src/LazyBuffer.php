<?php

declare(strict_types=1);

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

class LazyBuffer extends Buffer
{
    /**
     * @var array<TokenInterface>
     */
    protected array $buffer = [];

    /**
     * @var \Generator<int<0, max>, TokenInterface, mixed, mixed>
     */
    protected \Generator $stream;

    /**
     * @param iterable<TokenInterface> $stream
     */
    public function __construct(iterable $stream)
    {
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->stream = $this->toGenerator($stream);

        if ($this->stream->valid()) {
            /** @psalm-suppress MixedAssignment */
            $this->initial = $this->current = $this->stream->key();
            /** @psalm-suppress MixedArrayOffset */
            $this->buffer[$this->current] = $this->stream->current();

            $this->stream->next();
        }
    }

    /**
     * @param iterable<TokenInterface> $stream
     * @return \Generator<mixed, TokenInterface, mixed, mixed>
     */
    private function toGenerator(iterable $stream): \Generator
    {
        yield from $stream;
    }

    /**
     * @return int<0, max>
     */
    public function getBufferCurrentSize(): int
    {
        return \count($this->buffer);
    }

    public function seek($offset): void
    {
        if ($offset < $this->initial) {
            throw new \OutOfRangeException(
                \sprintf(self::ERROR_STREAM_POSITION_TO_LOW, $offset, $this->tokenToString($this->current()))
            );
        }

        //
        // In the case that the position value exceeds the part loaded
        // into the buffer, then it must be loaded into the memory of the
        // buffer.
        //
        while ($offset > ($last = \array_key_last($this->buffer))) {
            if (!$this->valid()) {
                throw new \OutOfRangeException(
                    \sprintf(self::ERROR_STREAM_POSITION_EXCEED, $offset, (string)$last)
                );
            }

            $this->next();
        }

        $this->current = $offset;
    }

    public function current(): TokenInterface
    {
        return $this->currentFrom($this->buffer);
    }

    public function valid(): bool
    {
        return isset($this->buffer[$this->current]);
    }

    public function next(): void
    {
        $this->nextValid();
    }

    protected function nextValid(): bool
    {
        ++$this->current;

        if (!isset($this->buffer[$this->current])) {
            $current = $this->stream->current();

            if ($current) {
                $this->buffer[$this->current] = $current;
                $this->stream->next();

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function key(): int
    {
        if (!$this->valid()) {
            return \array_key_last($this->buffer) ?? 0;
        }

        return parent::key();
    }
}
