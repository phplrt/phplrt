<?php

declare(strict_types=1);

namespace Phplrt\Visitor;

/**
 * The TraverserInterface allows to traverse groups of
 * nodes using visitor sets.
 */
interface TraverserInterface
{
    /**
     * If VisitorInterface::enter() returns DONT_TRAVERSE_CHILDREN,
     * child nodes of the current node will not be traversed for any
     * visitors.
     *
     * For subsequent visitors VisitorInterface::enter() will still be
     * called on the current node and VisitorInterface::leave() will also
     * be invoked for the current node.
     */
    public const DONT_TRAVERSE_CHILDREN = 0x01;

    /**
     * If VisitorInterface::enter() or VisitorInterface::::leave()
     * returns STOP_TRAVERSAL, traversal is aborted.
     *
     * The VisitorInterface::after() method will still be invoked.
     */
    public const STOP_TRAVERSAL = 0x02;

    /**
     * If VisitorInterface::leave() returns REMOVE_NODE for a node that
     * occurs in an array, it will be removed from the ast.
     *
     * For subsequent visitors VisitorInterface::leave() will still be
     * invoked for the removed node.
     */
    public const REMOVE_NODE = 0x03;

    /**
     * If VisitorInterface::enter() returns DONT_TRAVERSE_CURRENT_AND_CHILDREN,
     * child nodes of the current node will not be traversed for any visitors.
     *
     * For subsequent visitors VisitorInterface::enter() will not be called as
     * well. VisitorInterface::leave() will be invoked for visitors that has
     * VisitorInterface::enter() method invoked.
     */
    public const DONT_TRAVERSE_CURRENT_AND_CHILDREN = 0x04;

    /**
     * If VisitorInterface::leave() returns LOOP_ON_CURRENT,
     * child nodes of the current node will not be traversed for any visitors.
     *
     * For subsequent visitors VisitorInterface::enter() will not be called as
     * well. VisitorInterface::leave() will be invoked for visitors that has
     * VisitorInterface::enter() method invoked.
     */
    public const LOOP_ON_CURRENT = 0x05;

    /**
     * Adds a visitor.
     */
    public function with(VisitorInterface $visitor, bool $prepend = false): self;

    /**
     * Removes a visitor.
     */
    public function without(VisitorInterface $visitor): self;

    /**
     * Traverses the node and its descendants nodes using the
     * registered visitors.
     *
     * @param iterable<array-key, object> $nodes
     *
     * @return iterable<array-key, object>
     */
    public function traverse(iterable $nodes): iterable;
}
