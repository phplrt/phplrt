<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor;

use Phplrt\Contracts\Ast\NodeInterface;

abstract class Visitor implements VisitorInterface
{
    /**
     * {@inheritDoc}
     */
    public function before(iterable $nodes): ?iterable
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function enter(NodeInterface $node)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function leave(NodeInterface $node)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function after(iterable $nodes): ?iterable
    {
        return null;
    }
}
