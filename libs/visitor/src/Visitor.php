<?php

declare(strict_types=1);

namespace Phplrt\Visitor;

use Phplrt\Contracts\Ast\NodeInterface;

abstract class Visitor implements VisitorInterface
{
    public function before(iterable $nodes): ?iterable
    {
        return null;
    }

    public function enter(NodeInterface $node)
    {
        return null;
    }

    public function leave(NodeInterface $node)
    {
        return null;
    }

    public function after(iterable $nodes): ?iterable
    {
        return null;
    }
}
