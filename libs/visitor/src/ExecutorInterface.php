<?php

declare(strict_types=1);

namespace Phplrt\Visitor;

interface ExecutorInterface
{
    /**
     * @param iterable<object> $nodes
     * @return iterable<object>
     */
    public function execute(iterable $nodes): iterable;
}
