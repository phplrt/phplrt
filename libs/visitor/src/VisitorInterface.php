<?php

declare(strict_types=1);

namespace Phplrt\Visitor;

use Phplrt\Contracts\Ast\NodeInterface;

interface VisitorInterface
{
    /**
     * Called once before traversal.
     *
     * @param iterable|NodeInterface[]|NodeInterface $nodes
     * @return iterable|NodeInterface[]|NodeInterface|null
     */
    public function before(iterable $nodes): ?iterable;

    /**
     * Called when entering a node.
     *
     * @param NodeInterface $node
     * @return mixed
     */
    public function enter(NodeInterface $node);

    /**
     * Called when leaving a node.
     *
     * @param NodeInterface $node
     * @return mixed
     */
    public function leave(NodeInterface $node);

    /**
     * Called once after traversal.
     *
     * @param iterable|NodeInterface[]|NodeInterface $nodes
     * @return iterable|NodeInterface[]|NodeInterface|null
     */
    public function after(iterable $nodes): ?iterable;
}
