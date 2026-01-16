<?php

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Unit\Mutations;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\Exception\BadMethodException;
use Phplrt\Visitor\Executor;
use Phplrt\Visitor\Tests\Unit\Stub\Node;
use Phplrt\Visitor\Tests\Unit\TestCase;
use Phplrt\Visitor\Visitor;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('A set of tests that verify an AST modification using the Visitor::enter() method')]
class EnteringMutationsTest extends TestCase
{
    #[TestDox('Modifying a collection of AST nodes using array return')]
    public function testUpdateRootsByArrayWhenEntering(): void
    {
        $this->expectException(BadMethodException::class);
        $this->expectExceptionCode(Executor::ERROR_CODE_ARRAY_ENTERING);

        $this->traverse($original = $this->nodes(2), new class () extends Visitor {
            public function enter(NodeInterface $node): mixed
            {
                return $node instanceof Node && $node->getId() === 0 ? [] : $node;
            }
        });
    }

    #[TestDox('Modifying an AST node using array return')]
    public function testUpdateRootByArrayWhenEntering(): void
    {
        $this->expectException(BadMethodException::class);
        $this->expectExceptionCode(Executor::ERROR_CODE_ARRAY_ENTERING);

        $this->traverse($original = $this->node(), new class () extends Visitor {
            public function enter(NodeInterface $node): mixed
            {
                return $node instanceof Node && $node->getId() === 0 ? [] : $node;
            }
        });
    }

    #[TestDox('Modifying a collection of AST nodes using a new node object return')]
    public function testUpdateRootsByNodeWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->nodes(2), new class () extends Visitor {
            public function enter(NodeInterface $node): mixed
            {
                return $node instanceof Node && $node->getId() === 0 ? new Node(42) : $node;
            }
        });

        $this->assertEquals([new Node(42), new Node(42)], $actual);
        $this->assertNotSame($original, $actual);
    }

    #[TestDox('Modifying an AST node using a new node object return')]
    public function testUpdateRootByNodeWhenEntering(): void
    {
        $actual = $this->traverse($original = $this->node(), new class () extends Visitor {
            public function enter(NodeInterface $node): mixed
            {
                return $node instanceof Node && $node->getId() === 0 ? new Node(42) : $node;
            }
        });

        $this->assertEquals(new Node(42), $actual);
        $this->assertNotSame($original, $actual);
    }
}
