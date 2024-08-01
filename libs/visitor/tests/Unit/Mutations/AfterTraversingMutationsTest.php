<?php

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Unit\Mutations;

use Phplrt\Visitor\Tests\Unit\Stub\Node;
use Phplrt\Visitor\Tests\Unit\TestCase;
use Phplrt\Visitor\Visitor;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('A set of tests that verify an AST modification using the Visitor::after() method.')]
class AfterTraversingMutationsTest extends TestCase
{
    #[TestDox('Modifying a collection of AST nodes using array return')]
    public function testUpdateRootsByArrayWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->nodes(2), new class () extends Visitor {
            public function after(iterable $nodes): ?iterable
            {
                return \is_array($nodes) ? [] : null;
            }
        });

        $this->assertSame([], $actual);
        $this->assertNotSame($original, $actual);
    }

    #[TestDox('Modifying an AST node using array return')]
    public function testUpdateRootByArrayWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->node(), new class () extends Visitor {
            public function after(iterable $nodes): ?iterable
            {
                return $nodes instanceof Node && $nodes->getId() === 0 ? [] : $nodes;
            }
        });

        $this->assertSame([], $actual);
        $this->assertNotSame($original, $actual);
    }

    #[TestDox('Modifying a collection of AST nodes using a new node object return')]
    public function testUpdateRootsByNodeWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->nodes(2), new class () extends Visitor {
            public function after(iterable $nodes): ?iterable
            {
                return \is_array($nodes) ? new Node(42) : null;
            }
        });

        $this->assertEquals(new Node(42), $actual);
        $this->assertNotSame($original, $actual);
    }

    #[TestDox('Modifying an AST node using a new node object return')]
    public function testUpdateRootByNodeWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->node(), new class () extends Visitor {
            public function after(iterable $nodes): ?iterable
            {
                return $nodes instanceof Node && $nodes->getId() === 0 ? new Node(42) : $nodes;
            }
        });

        $this->assertEquals(new Node(42), $actual);
        $this->assertNotSame($original, $actual);
    }
}
