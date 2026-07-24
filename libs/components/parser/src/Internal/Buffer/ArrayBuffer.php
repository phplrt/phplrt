<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Buffer;

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
        $tokens = \iterator_to_array($tokens, false);

        if ($tokens === []) {
            throw new \OutOfRangeException('Buffer must contain at least one token');
        }

        $this->tokens = $tokens;
        $this->size = \count($tokens);
        $this->current = $tokens[0];
    }

    public function seek(int $offset): void
    {
        $size = $this->size;

        if ($offset < 0 || $offset > $size) {
            throw OutOfRangeException::becausePositionOutOfRange($offset, $size);
        }

        if ($offset < $size) {
            $this->key = $offset;
            $this->current = $this->tokens[$offset];
            $this->isValid = true;

            return;
        }

        $this->key = $size - 1;
        $this->current = $this->tokens[$size - 1];
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
        $this->isValid = true;
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
