<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Lexer\BufferInterface;

/**
 * Class ArrayBuffer
 */
class ArrayBuffer implements BufferInterface
{
    /**
     * @var int
     */
    private $current = 0;

    /**
     * @var array|TokenInterface[]
     */
    private $buffer;

    /**
     * @var int
     */
    private $size;

    /**
     * Buffer constructor.
     *
     * @param \Generator $stream
     */
    public function __construct(\Generator $stream)
    {
        $this->buffer = \iterator_to_array($stream, false);
        $this->size = \count($this->buffer);
    }

    /**
     * {@inheritDoc}
     */
    public function current(): TokenInterface
    {
        return $this->buffer[$this->current] ?? $this->buffer[\array_key_last($this->buffer)];
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        if ($this->current < $this->size - 1) {
            ++$this->current;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function key(): int
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return $this->current < $this->size;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->current = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function seek(int $position): void
    {
        $this->current = $position;
    }
}
