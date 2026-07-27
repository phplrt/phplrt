<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * The token that has entered an embedded lexer, along with everything that
 * lexer has read.
 *
 * The token is written the way it is written in the source, so its value is
 * the fragment that entered the embedded lexer rather than the fragment read
 * by it: where the reading has stopped is told by {@see TokenEmbedding::$end}.
 *
 * @template-implements \IteratorAggregate<int, TokenInterface>
 * @template-implements \ArrayAccess<int, TokenInterface>
 *
 * @readonly
 */
final class TokenEmbedding extends Token implements
    \IteratorAggregate,
    \ArrayAccess,
    \Countable
{
    /**
     * @param non-empty-string|null $name
     * @param int<0, max> $offset
     * @param int<0, max> $end
     * @param list<TokenInterface> $children
     * @param list<string> $captures
     */
    public function __construct(
        int $id,
        ?string $name,
        ChannelInterface $channel,
        string $value,
        int $offset,
        int $end,
        /**
         * The tokens the embedded lexer has read, in the order it has read
         * them.
         */
        public array $children = [],
        array $captures = [],
    ) {
        // The token spans as far as the embedded lexer has read, rather than
        // as far as its own value does
        parent::__construct($id, $name, $channel, $value, $offset, $captures, $end);
    }

    /**
     * Creates the embedding of the given token.
     *
     * @param list<TokenInterface> $children
     * @param int<0, max> $end
     */
    public static function createFromToken(Token $token, array $children, int $end): self
    {
        return new self(
            id: $token->id,
            name: $token->name,
            channel: $token->channel,
            value: $token->value,
            offset: $token->offset,
            end: $end,
            children: $children,
            captures: $token->captures,
        );
    }

    /**
     * @return \Traversable<int, TokenInterface>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->children[$offset]);
    }

    public function offsetGet(mixed $offset): TokenInterface
    {
        return $this->children[$offset] ?? throw new \OutOfRangeException(
            'The embedded lexer has read no token at the given position',
        );
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \LogicException('A token is immutable');
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \LogicException('A token is immutable');
    }

    public function count(): int
    {
        return \count($this->children);
    }
}
