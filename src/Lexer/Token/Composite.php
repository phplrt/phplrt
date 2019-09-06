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
class Composite extends Token implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @var array|TokenInterface[]
     */
    private $children;

    /**
     * BaseToken constructor.
     *
     * @param array|TokenInterface[] $children
     */
    public function __construct(array $children)
    {
        $first          = \array_shift($children);
        $this->children = $children;

        parent::__construct($first->getName(), $first->getValue(), $first->getOffset());
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
