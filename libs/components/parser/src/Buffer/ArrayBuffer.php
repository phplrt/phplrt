<?php

declare(strict_types=1);

namespace Phplrt\Parser\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Exception\OutOfRangeException;

/**
 * A plain token cursor over a list of tokens.
 *
 * This implementation forcibly loads all tokens into memory (as array list).
 *
 * @template-covariant TToken of TokenInterface = TokenInterface
 *
 * @template-implements BufferInterface<TToken>
 */
final class ArrayBuffer implements BufferInterface
{
    /**
     * @var non-empty-list<TToken>
     */
    private readonly array $tokens;

    /**
     * @var int<0, max>
     */
    private readonly int $size;

    /**
     * Whether the cursor still points at a "not-yet-consumed token". It goes
     * {@see false} as soon as an attempt is made to step past the last one.
     */
    private bool $isValid = true;

    /**
     * @var TToken
     */
    public private(set) TokenInterface $current;

    /**
     * @var int<0, max>
     */
    public private(set) int $key = 0;

    /**
     * @param iterable<mixed, TToken> $tokens
     *
     * @throws \OutOfRangeException in case of token stream is empty
     */
    public function __construct(iterable $tokens)
    {
        $this->tokens = \iterator_to_array($tokens, false);
        $this->size = \count($this->tokens);

        if ($this->size === 0) {
            throw new \OutOfRangeException('Buffer must contain at least one token');
        }

        $this->current = $this->tokens[0];
    }

    public function seek(int $offset): void
    {
        if ($offset < 0 || $offset > $this->size) {
            throw OutOfRangeException::becausePositionOutOfRange($offset, $this->size);
        }

        if ($offset < $this->size) {
            $this->key = $offset;
            $this->current = $this->tokens[$offset];
            $this->isValid = true;

            return;
        }

        // Seeking exactly onto the past-the-end position keeps the cursor on the
        // terminal token and only marks the buffer as exhausted.
        $this->key = $this->size - 1;
        $this->current = $this->tokens[$this->size - 1];
        $this->isValid = false;
    }

    public function current(): TokenInterface
    {
        return $this->current;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return $this->isValid;
    }

    public function rewind(): void
    {
        $this->key = 0;
        $this->current = $this->tokens[0];
        $this->isValid = $this->size > 0;
    }

    public function next(): void
    {
        $next = $this->key + 1;

        if ($next < $this->size) {
            $this->key = $next;
            $this->current = $this->tokens[$next];

            return;
        }

        $this->isValid = false;
    }
}
