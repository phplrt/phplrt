<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Mutations;

use Phplrt\Visitor\Visitor;
use Phplrt\Visitor\Tests\TestCase;
use Phplrt\Visitor\Tests\Stub\Node;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\Exception\BadMethodException;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class LeavingMutationsTestCase
 *
 * @testdox A set of tests that verify an AST modification using the Visitor::leave() method.
 */
class LeavingMutationsTestCase extends TestCase
{
    /**
     * @testdox Modifying a collection of AST nodes using array return
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testUpdateRootsByArrayWhenLeaving(): void
    {
        $actual = $this->traverse($original = $this->nodes(2), new class () extends Visitor {
            public function leave(NodeInterface $node)
            {
                return $node instanceof Node && $node->getId() === 0 ? [] : $node;
            }
        });

        $this->assertSame([], $actual);
        $this->assertNotSame($original, $actual);
    }

    /**
     * @testdox Modifying an AST node using array return
     *
     * @return void
     */
    public function testUpdateRootByArrayWhenLeaving(): void
    {
        $this->expectException(BadMethodException::class);

        $this->traverse($original = $this->node(), new class () extends Visitor {
            public function leave(NodeInterface $node)
            {
                return $node instanceof Node && $node->getId() === 0 ? [] : $node;
            }
        });
    }

    /**
     * @testdox Modifying a collection of AST nodes using a new node object return
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testUpdateRootsByNodeWhenLeaving(): void
    {
        $actual = $this->traverse($original = $this->nodes(2), new class () extends Visitor {
            public function leave(NodeInterface $node)
            {
                return $node instanceof Node && $node->getId() === 0 ? new Node(42) : $node;
            }
        });

        $this->assertEquals([new Node(42), new Node(42)], $actual);
        $this->assertNotSame($original, $actual);
    }

    /**
     * @testdox Modifying an AST node using a new node object return
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testUpdateRootByNodeWhenLeaving(): void
    {
        $actual = $this->traverse($original = $this->node(), new class () extends Visitor {
            public function leave(NodeInterface $node)
            {
                return $node instanceof Node && $node->getId() === 0 ? new Node(42) : $node;
            }
        });

        $this->assertEquals(new Node(42), $actual);
        $this->assertNotSame($original, $actual);
    }
}
