<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

class ArrayBuffer extends Buffer
{
    /**
     * @var array<TokenInterface>
     */
    private array $buffer;

    /**
     * @var int
     */
    private int $size;

    /**
     * @param iterable<TokenInterface> $stream
     */
    public function __construct(iterable $stream)
    {
        $this->buffer = $stream instanceof \Traversable
            ? \iterator_to_array($stream, false)
            : $stream;

        $this->size = \count($this->buffer);

        if (\count($this->buffer)) {
            $this->initial = $this->current = \array_key_first($this->buffer);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function seek(int $offset): void
    {
        if ($offset < $this->initial) {
            $message = \sprintf(static::ERROR_STREAM_POSITION_TO_LOW, $offset, $this->current());

            throw new \OutOfRangeException($message);
        }

        if ($offset > ($last = \array_key_last($this->buffer))) {
            $message = \sprintf(static::ERROR_STREAM_POSITION_EXCEED, $offset, $last);

            throw new \OutOfRangeException($message);
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
