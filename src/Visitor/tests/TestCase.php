<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor\Tests;

use Phplrt\Visitor\Traverser;
use Phplrt\Visitor\Tests\Stub\Node;
use Phplrt\Visitor\VisitorInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use PHPUnit\Framework\TestCase as BastTestCase;

/**
 * Class TestCase
 */
abstract class TestCase extends BastTestCase
{
    /**
     * @var int
     */
    protected const NODES_COUNT_STUB = 11;

    /**
     * @return NodeInterface
     */
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

    /**
     * @param int $repetitions
     * @return array
     */
    protected function nodes(int $repetitions = 1): array
    {
        $result = [];

        for ($i = 0; $i < $repetitions; ++$i) {
            $result[] = $this->node();
        }

        return $result;
    }

    /**
     * @param iterable $ast
     * @param VisitorInterface $visitor
     * @return iterable
     */
    protected function traverse(iterable $ast, VisitorInterface $visitor): iterable
    {
        return (new Traverser())->with($visitor)->traverse($ast);
    }
}
