<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Tests\Visitor;

use Phplrt\Tests\Visitor\Stub\Counter;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class TraversableTestCase
 *
 * @testdox A set of tests that count the number of passes by nodes.
 */
class TraversableTestCase extends TestCase
{
    /**
     * @testdox Counting the number of Visitor::before() method calls using AST node
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testNodeBefore(): void
    {
        $this->traverse($this->node(), $counter = new Counter());

        $this->assertSame(1, $counter->before);
    }

    /**
     * @testdox Counting the number of Visitor::before() method calls using array of AST nodes
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testNodesBefore(): void
    {
        $this->traverse($this->nodes(2), $counter = new Counter());

        $this->assertSame(1, $counter->before);
    }

    /**
     * @testdox Counting the number of Visitor::after() method calls using AST node
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testNodeAfter(): void
    {
        $this->traverse($this->node(), $counter = new Counter());

        $this->assertSame(1, $counter->after);
    }

    /**
     * @testdox Counting the number of Visitor::after() method calls using array of AST nodes
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testNodesAfter(): void
    {
        $this->traverse($this->nodes(2), $counter = new Counter());

        $this->assertSame(1, $counter->after);
    }

    /**
     * @testdox Counting the number of Visitor::enter() method calls using AST node
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testNodeEnter(): void
    {
        $this->traverse($this->node(), $counter = new Counter());

        $this->assertSame(self::NODES_COUNT_STUB, $counter->enter);
    }

    /**
     * @testdox Counting the number of Visitor::enter() method calls using array of AST nodes
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testNodesEnter(): void
    {
        $this->traverse($this->nodes(2), $counter = new Counter());

        $this->assertSame(self::NODES_COUNT_STUB * 2, $counter->enter);
    }

    /**
     * @testdox Counting the number of Visitor::leave() method calls using AST node
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testNodeLeave(): void
    {
        $this->traverse($this->node(), $counter = new Counter());

        $this->assertSame(self::NODES_COUNT_STUB, $counter->leave);
    }

    /**
     * @testdox Counting the number of Visitor::leave() method calls using array of AST nodes
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testNodesLeave(): void
    {
        $this->traverse($this->nodes(2), $counter = new Counter());

        $this->assertSame(self::NODES_COUNT_STUB * 2, $counter->leave);
    }
}
