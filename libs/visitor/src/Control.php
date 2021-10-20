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
 * @psalm-type ControlEnum = Control::*
 */
interface Control
{
    /**
     * If {@see VisitorInterface::enter()}
     * returns {@see Control::DONT_TRAVERSE_CHILDREN}, child nodes of the
     * current node will not be traversed for any visitors.
     *
     * For subsequent visitors {@see VisitorInterface::enter()} will still be
     * called on the current node and {@see VisitorInterface::leave()} will also
     * be invoked for the current node.
     *
     * @var ControlEnum
     */
    public const DONT_TRAVERSE_CHILDREN = 0x01;

    /**
     * If {@see VisitorInterface::enter()} or {@see VisitorInterface::::leave()}
     * returns {@see Control::STOP_TRAVERSAL}, traversal is aborted.
     *
     * The {@see VisitorInterface::after()} method will still be invoked.
     *
     * @var ControlEnum
     */
    public const STOP_TRAVERSAL = 0x02;

    /**
     * If {@see VisitorInterface::leave()} returns {@see Control::REMOVE_NODE}
     * for a node that occurs in an array, it will be removed from the ast.
     *
     * For subsequent visitors {@see VisitorInterface::leave()} will still be
     * invoked for the removed node.
     *
     * @var ControlEnum
     */
    public const REMOVE_NODE = 0x03;

    /**
     * If {@see VisitorInterface::enter()}
     * returns {@see Control::DONT_TRAVERSE_CURRENT_AND_CHILDREN}, child nodes
     * of the current node will not be traversed for any visitors.
     *
     * For subsequent visitors {@see VisitorInterface::enter()} will not be
     * called as well. {@see VisitorInterface::leave()} will be invoked for
     * visitors that has {@see VisitorInterface::enter()} method invoked.
     *
     * @var ControlEnum
     */
    public const DONT_TRAVERSE_CURRENT_AND_CHILDREN = 0x04;

    /**
     * If {@see VisitorInterface::leave()}
     * returns {@see Control::LOOP_ON_CURRENT}, child nodes of the current node
     * will not be traversed for any visitors.
     *
     * For subsequent visitors {@see VisitorInterface::enter()} will not be
     * called as well. {@see VisitorInterface::leave()} will be invoked for
     * visitors that has {@see VisitorInterface::enter()} method invoked.
     *
     * @var ControlEnum
     */
    public const LOOP_ON_CURRENT = 0x05;
}
