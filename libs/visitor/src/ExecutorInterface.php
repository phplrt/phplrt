<?php

declare(strict_types=1);

namespace Phplrt\Visitor;

interface ExecutorInterface
{
    /**
     * Traverses an array of nodes using the registered visitors.
     *
     * @param iterable<array-key, object> $nodes list of nodes
     *
     * @return iterable<array-key, object> Traversed list of nodes
     */
    public function execute(iterable $nodes): iterable;
}
