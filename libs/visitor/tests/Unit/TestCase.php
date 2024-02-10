<?php

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Unit;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\Tests\TestCase as BaseTestCase;
use Phplrt\Visitor\Tests\Unit\Stub\Node;
use Phplrt\Visitor\Traverser;
use Phplrt\Visitor\VisitorInterface;

abstract class TestCase extends BaseTestCase
{
    protected const NODES_COUNT_STUB = 11;

    protected function node(): NodeInterface
    {
        return new Node(0, [
            new Node(1, [
                new Node(2),
                new Node(3, [
                    new Node(4),
                    new Node(5),
                    new Node(6, [
                        new Node(7),
                        new Node(8),
                        new Node(9),
                        new Node(10),
                    ]),
                ]),
            ]),
        ]);
    }

    protected function nodes(int $repetitions = 1): array
    {
        $result = [];

        for ($i = 0; $i < $repetitions; ++$i) {
            $result[] = $this->node();
        }

        return $result;
    }

    protected function traverse(iterable $ast, VisitorInterface $visitor): iterable
    {
        return (new Traverser())->with($visitor)->traverse($ast);
    }
}
