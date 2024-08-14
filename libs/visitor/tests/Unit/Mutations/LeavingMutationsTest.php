<?php

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Unit\Mutations;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\Exception\BadMethodException;
use Phplrt\Visitor\Tests\Unit\Stub\Node;
use Phplrt\Visitor\Tests\Unit\TestCase;
use Phplrt\Visitor\Visitor;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('A set of tests that verify an AST modification using the Visitor::leave() method')]
class LeavingMutationsTest extends TestCase
{
    #[TestDox('Modifying a collection of AST nodes using array return')]
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

    #[TestDox('Modifying an AST node using array return')]
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

    #[TestDox('Modifying a collection of AST nodes using a new node object return')]
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

    #[TestDox('Modifying an AST node using a new node object return')]
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
