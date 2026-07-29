<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Exception\OutOfRangeException;

/**
 * A cursor over a list of tokens held in memory.
 *
 * @template-covariant TToken of TokenInterface = TokenInterface
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
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
     * The position of the terminal token, i.e. where the cursor stops.
     *
     * @var int<0, max>
     */
    private readonly int $lastIndex;

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
     * @throws \OutOfRangeException in case of token stream is empty
     */
    public function __construct(iterable $tokens)
    {
        $tokens = match (true) {
            !\is_array($tokens) => \iterator_to_array($tokens, false),
            \array_is_list($tokens) => $tokens,
            default => \array_values($tokens),
        };

        if ($tokens === []) {
            throw new \OutOfRangeException('Buffer must contain at least one token');
        }

        $this->tokens = $tokens;
        $this->lastIndex = \count($tokens) - 1;
        $this->current = $tokens[0];
    }

    public function seek(int $offset): void
    {
        if ($offset < 0 || $offset > $this->lastIndex) {
            throw OutOfRangeException::becausePositionOutOfRange($offset, $this->lastIndex);
        }

        $this->key = $offset;
        $this->current = $this->tokens[$offset];
    }

    public function next(): void
    {
        $next = $this->key + 1;

        if ($next > $this->lastIndex) {
            return;
        }

        $this->key = $next;
        $this->current = $this->tokens[$next];
    }
}
