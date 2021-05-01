<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

final class Composite extends Token implements CompositeTokenInterface
{
    /**
     * @var array<TokenInterface>
     */
    private array $children;

    /**
     * @param string $name
     * @param string $value
     * @param positive-int|0 $offset
     * @param array<TokenInterface> $children
     */
    public function __construct(string $name, string $value, int $offset, array $children)
    {
        $this->children = $children;

        parent::__construct($name, $value, $offset);
    }

    /**
     * @param non-empty-array<TokenInterface> $tokens
     * @return self
     */
    public static function fromArray(array $tokens): self
    {
        assert(count($tokens) > 0);

        $first = \array_shift($tokens);

        return new self($first->getName(), $first->getValue(), $first->getOffset(), $tokens);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return \array_merge(parent::jsonSerialize(), [
            'children' => $this->children,
        ]);
    }

    /**
     * @return \Traversable<array-key, TokenInterface>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * @param positive-int|0 $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        assert(is_int($offset) && $offset >= 0);

        return isset($this->children[$offset]);
    }

    /**
     * @param positive-int|0 $offset
     * @return TokenInterface|null
     */
    public function offsetGet($offset): ?TokenInterface
    {
        assert(is_int($offset) && $offset >= 0);

        return $this->children[$offset] ?? null;
    }

    /**
     * @param positive-int|0 $offset
     * @param TokenInterface $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        assert(is_int($offset) && $offset >= 0);
        assert($value instanceof TokenInterface);

        $this->children[$offset] = $value;
    }

    /**
     * @param positive-int|0 $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        assert(is_int($offset) && $offset >= 0);

        unset($this->children[$offset]);
    }

    /**
     * @return positive-int
     * @psalm-suppress InvalidReturnType (Count of children tokens greater than 0)
     */
    public function count(): int
    {
        /**
         * @psalm-suppress InvalidReturnStatement (Count of children tokens greater than 0)
         */
        return \count($this->children);
    }
}
