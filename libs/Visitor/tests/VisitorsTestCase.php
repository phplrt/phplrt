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
use Phplrt\Visitor\Tests\Stub\Counter;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class VisitorsTestCase
 *
 * @testdox A set of tests that check the interaction of Visitor instances with the Traversable container.
 */
class VisitorsTestCase extends TestCase
{
    /**
     * @testdox Check that the visitor worked if added
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testVisitorAppending(): void
    {
        (new Traverser())
            ->with($a = new Counter())
            ->traverse($this->node());

        $this->assertWasCalled($a);
    }

    /**
     * @testdox Check that the several visitor worked if added
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testVisitorsAppending(): void
    {
        (new Traverser())
            ->with($a = new Counter())
            ->with($b = new Counter())
            ->traverse($this->node());

        $this->assertWasCalled($a);
        $this->assertWasCalled($b);
    }

    /**
     * @testdox Check that the several visitor is ignored if deleted
     *
     * @return void
     * @throws ExpectationFailedException
     */
    public function testVisitorsRemoving(): void
    {
        (new Traverser())
            ->with($a = new Counter())
            ->with($b = new Counter())
            ->without($a)
            ->traverse($this->node());

        $this->assertWasNotCalled($a);
        $this->assertWasCalled($b);
    }

    /**
     * @param Counter $visitor
     * @return void
     * @throws ExpectationFailedException
     */
    private function assertWasCalled(Counter $visitor): void
    {
        $this->assertGreaterThan(0, $visitor->before);
        $this->assertGreaterThan(0, $visitor->after);
        $this->assertGreaterThan(0, $visitor->enter);
        $this->assertGreaterThan(0, $visitor->leave);
    }

    /**
     * @param Counter $visitor
     * @return void
     * @throws ExpectationFailedException
     */
    private function assertWasNotCalled(Counter $visitor): void
    {
        $this->assertSame(0, $visitor->before);
        $this->assertSame(0, $visitor->after);
        $this->assertSame(0, $visitor->enter);
        $this->assertSame(0, $visitor->leave);
    }
}
