<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Ast\ProvidesChildrenInterface;

/**
 * Trait ChildrenNodesTrait
 *
 * @mixin ProvidesChildrenInterface
 */
trait ChildrenNodesTrait
{
    /**
     * {@inheritDoc}
     */
    protected $children = [];

    /**
     * @return array
     */
    public function getChildNodeNames(): array
    {
        return \array_keys($this->children);
    }

    /**
     * {@inheritDoc}
     * @internal An IteratorAggregate::getIterator() implementation
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->getChildNodeNames() as $name) {
            yield $this->$name;
        }
    }

    /**
     * {@inheritDoc}
     * @internal A Countable::count() implementation
     */
    public function count(): int
    {
        return \count($this->children);
    }

    /**
     * {@inheritDoc}
     * @internal An ArrayAccess::offsetGet() implementation
     */
    public function offsetGet($index): ?NodeInterface
    {
        \assert(\is_scalar($index));

        if (\is_string($index) && \property_exists($this, $index)) {
            return $this->$index;
        }

        return $this->children[$index] ?? null;
    }

    /**
     * {@inheritDoc}
     * @internal An ArrayAccess::offsetSet() implementation
     * @throws \OutOfBoundsException
     */
    public function offsetSet($index, $node): void
    {
        \assert(\is_scalar($index) || $index === null);
        \assert($node instanceof NodeInterface || $node === null);

        if ($index === null && $node === null) {
            throw new \OutOfBoundsException('Can not set null value');
        }

        switch (true) {
            case $index === null && $node === null:
                throw new \OutOfBoundsException('Can not set null value');

            case $index === null:
                $this->children[] = $node;
                break;

            case $node === null:
                $this->offsetUnset($index);
                break;

            default:
                if (\is_string($index) && \property_exists($this, $index)) {
                    $this->$index = $node;
                    return;
                }

                $this->children[$index] = $node;
        }
    }

    /**
     * {@inheritDoc}
     * @internal An ArrayAccess::offsetExists() implementation
     */
    public function offsetExists($index): bool
    {
        \assert(\is_scalar($index));

        return isset($this->children[$index]) || \array_key_exists($index, $this->children);
    }

    /**
     * {@inheritDoc}
     * @internal An ArrayAccess::offsetUnset() implementation
     */
    public function offsetUnset($index): void
    {
        \assert(\is_scalar($index));

        if (\is_string($index) && \property_exists($this, $index)) {
            $this->$index = null;
        }

        unset($this->children[$index]);
    }
}
