<?php

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Mutations;

use Phplrt\Visitor\Visitor;
use Phplrt\Visitor\Tests\TestCase;
use Phplrt\Visitor\Tests\Stub\Node;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class BeforeTraversingMutationsTestCase
 *
 * @testdox A set of tests that verify an AST modification using the Visitor::before() method.
 */
class BeforeTraversingMutationsTestCase extends TestCase
{
    /**
     * @testdox Modifying a collection of AST nodes using array return
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testUpdateRootsByArrayWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->nodes(2), new class () extends Visitor {
            public function before(iterable $node): ?iterable
            {
                return \is_array($node) ? [] : null;
            }
        });

        $this->assertSame([], $actual);
        $this->assertNotSame($original, $actual);
    }

    /**
     * @testdox Modifying an AST node using array return
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testUpdateRootByArrayWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->node(), new class () extends Visitor {
            public function before(iterable $node): ?iterable
            {
                return $node instanceof Node && $node->getId() === 0 ? [] : $node;
            }
        });

        $this->assertSame([], $actual);
        $this->assertNotSame($original, $actual);
    }

    /**
     * @testdox Modifying a collection of AST nodes using a new node object return
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testUpdateRootsByNodeWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->nodes(2), new class () extends Visitor {
            public function before(iterable $node): ?iterable
            {
                return \is_array($node) ? new Node(42) : null;
            }
        });

        $this->assertEquals(new Node(42), $actual);
        $this->assertNotSame($original, $actual);
    }

    /**
     * @testdox Modifying an AST node using a new node object return
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testUpdateRootByNodeWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->node(), new class () extends Visitor {
            public function before(iterable $node): ?iterable
            {
                return $node instanceof Node && $node->getId() === 0 ? new Node(42) : $node;
            }
        });

        $this->assertEquals(new Node(42), $actual);
        $this->assertNotSame($original, $actual);
    }
}
