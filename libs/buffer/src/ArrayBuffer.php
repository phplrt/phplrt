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
     * @var array<positive-int|0, TokenInterface>
     */
    private array $buffer;

    /**
     * @var positive-int|0
     */
    private int $size;

    /**
     * @param iterable<TokenInterface> $stream
     */
    public function __construct(iterable $stream)
    {
        $this->buffer = $this->iterableToArray($stream);
        $this->size = \count($this->buffer);

        if (\count($this->buffer)) {
            /** @psalm-suppress MixedPropertyTypeCoercion */
            $this->initial = $this->current = \array_key_first($this->buffer);
        }
    }

    /**
     * @param iterable<TokenInterface> $tokens
     * @return array<positive-int|0, TokenInterface>
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MixedReturnTypeCoercion
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
                \sprintf(self::ERROR_STREAM_POSITION_TO_LOW, $offset, (string)$this->current())
            );
        }

        if ($offset > ($last = \array_key_last($this->buffer))) {
            throw new \OutOfRangeException(
                \sprintf(self::ERROR_STREAM_POSITION_EXCEED, $offset, (string)$last)
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
