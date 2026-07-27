<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Printer\PrettyTokenPrinter;

/**
 * A token carrying the tokens another lexer has read.
 *
 * The tokens of an embedded lexer never reach the stream of the lexer that
 * called it: they are its own business and are only reachable through the
 * token that entered it.
 *
 * @template-implements \IteratorAggregate<int, TokenInterface>
 * @template-implements \ArrayAccess<int, TokenInterface>
 */
abstract readonly class CompositeToken implements
    TokenInterface,
    \IteratorAggregate,
    \ArrayAccess,
    \Countable
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $end
     * @param list<TokenInterface> $children
     */
    public function __construct(
        public int $id,
        /**
         * @var non-empty-string|null
         */
        public ?string $name,
        public ChannelInterface $channel,
        public string $value,
        public int $offset,
        public int $end,
        /**
         * The tokens the embedded lexer has read, in the order it has read
         * them.
         */
        public array $children = [],
    ) {}

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

    public function __toString(): string
    {
        /** @var PrettyTokenPrinter $printer */
        static $printer = new PrettyTokenPrinter();

        return $printer->print($this);
    }
}
