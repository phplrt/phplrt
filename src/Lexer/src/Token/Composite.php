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

/**
 * Class Composite
 */
class Composite extends Token implements CompositeTokenInterface
{
    /**
     * @var array|TokenInterface[]
     */
    private $children;

    /**
     * BaseToken constructor.
     *
     * @param string|int $name
     * @param string $value
     * @param int $offset
     * @param array|TokenInterface[] $children
     */
    public function __construct($name, string $value, int $offset, array $children)
    {
        $this->children = $children;

        parent::__construct($name, $value, $offset);
    }

    /**
     * @param array|TokenInterface[] $tokens
     * @return static
     */
    public static function fromArray(array $tokens): self
    {
        \assert(\count($tokens) > 0);

        $first = \array_shift($tokens);

        return new static($first->getName(), $first->getValue(), $first->getOffset(), $tokens);
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
     * @return \Traversable|TokenInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        \assert(\is_int($offset));

        return isset($this->children[$offset]);
    }

    /**
     * @param int $offset
     * @return TokenInterface|null
     */
    public function offsetGet($offset): ?TokenInterface
    {
        \assert(\is_int($offset));

        return $this->children[$offset] ?? null;
    }

    /**
     * @param int $offset
     * @param TokenInterface $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        \assert(\is_int($offset));
        \assert($value instanceof TokenInterface);

        $this->children[$offset] = $value;
    }

    /**
     * @param int $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        \assert(\is_int($offset));

        unset($this->children[$offset]);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->children);
    }
}
