<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor;

/**
 * The TraverserInterface allows to traverse groups of
 * nodes using visitor sets.
 */
interface TraverserInterface
{
    /**
     * Adds a visitor.
     *
     * @psalm-immutable
     * @param VisitorInterface $visitor
     * @param bool $prepend
     * @return TraverserInterface
     */
    public function with(VisitorInterface $visitor, bool $prepend = false): self;

    /**
     * Removes a visitor.
     *
     * @psalm-immutable
     * @param VisitorInterface $visitor
     * @return TraverserInterface
     */
    public function without(VisitorInterface $visitor): self;

    /**
     * Traverses the node and its descendants nodes using the
     * registered visitors.
     *
     * @template T of iterable
     * @param T $node
     * @return T
     */
    public function traverse(iterable $node): iterable;
}
