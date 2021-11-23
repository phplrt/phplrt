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
    final public const BUFFER_MIN_SIZE = 1;

    /**
     * @var int
     */
    public const BUFFER_DEFAULT_SIZE = 100;

    /**
     * ExtrusiveBuffer constructor.
     *
     * @param iterable<TokenInterface> $stream
     * @param positive-int $size
     */
    public function __construct(
        iterable $stream,
        private readonly int $size = self::BUFFER_DEFAULT_SIZE
    ) {
        assert($this->size > 0, new \InvalidArgumentException(
            'Buffer size must be greater than 0, but ' . $size . ' passed'
        ));

        parent::__construct($stream);
    }

    /**
     * @return positive-int
     */
    public function getBufferSize(): int
    {
        return $this->size;
    }

    /**
     * {@inheritDoc}
     */
    public function seek(int $offset): void
    {
        if ($offset < \array_key_first($this->buffer)) {
            $message = \sprintf(self::ERROR_BUFFER_MEMORY_ALREADY_FREED, $offset, $this->size);

            throw new \OutOfBoundsException($message);
        }

        parent::seek($offset);
    }

    /**
     * @return void
     */
    public function next(): void
    {
        if ($this->nextValid() && $this->getBufferCurrentSize() > $this->size) {
            /** @psalm-suppress PossiblyNullArrayOffset */
            unset($this->buffer[\array_key_first($this->buffer)]);
        }
    }
}
