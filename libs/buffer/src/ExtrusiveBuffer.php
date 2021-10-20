<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Buffer;

class ExtrusiveBuffer extends LazyBuffer
{
    /**
     * @var string
     */
    private const ERROR_BUFFER_MEMORY_ALREADY_FREED =
        'Can not to seek to position %d, because memory allocated for %d elements in this area ' .
        'of the buffer has already been freed';

    /**
     * @var int
     */
    public const BUFFER_MIN_SIZE = 1;

    /**
     * @var int
     */
    public const BUFFER_DEFAULT_SIZE = 100;

    /**
     * @var int
     */
    private int $size;

    /**
     * ExtrusiveBuffer constructor.
     *
     * @param iterable $stream
     * @param int $size
     */
    public function __construct(iterable $stream, int $size = self::BUFFER_DEFAULT_SIZE)
    {
        $this->size = \max(self::BUFFER_MIN_SIZE, $size);

        parent::__construct($stream);
    }

    /**
     * @return int
     */
    public function getBufferSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $position
     * @return void
     */
    public function seek($position): void
    {
        if ($position < \array_key_first($this->buffer)) {
            $message = \sprintf(self::ERROR_BUFFER_MEMORY_ALREADY_FREED, $position, $this->size);

            throw new \OutOfBoundsException($message);
        }

        parent::seek($position);
    }

    /**
     * @return void
     */
    public function next(): void
    {
        if ($this->nextValid() && $this->getBufferCurrentSize() > $this->size) {
            unset($this->buffer[\array_key_first($this->buffer)]);
        }
    }
}
