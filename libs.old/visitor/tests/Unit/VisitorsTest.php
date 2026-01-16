<?php

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Unit;

use Phplrt\Visitor\Tests\Unit\Stub\Counter;
use Phplrt\Visitor\Traverser;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('A set of tests that check the interaction of Visitor instances with the Traversable container')]
class VisitorsTest extends TestCase
{
    #[TestDox('Check that the visitor worked if added')]
    public function testVisitorAppending(): void
    {
        (new Traverser())
            ->with($a = new Counter())
            ->traverse($this->node());

        $this->assertWasCalled($a);
    }

    #[TestDox('Check that the several visitor worked if added')]
    public function testVisitorsAppending(): void
    {
        (new Traverser())
            ->with($a = new Counter())
            ->with($b = new Counter())
            ->traverse($this->node());

        $this->assertWasCalled($a);
        $this->assertWasCalled($b);
    }

    #[TestDox('Check that the several visitor is ignored if deleted')]
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

    private function assertWasCalled(Counter $visitor): void
    {
        $this->assertGreaterThan(0, $visitor->before);
        $this->assertGreaterThan(0, $visitor->after);
        $this->assertGreaterThan(0, $visitor->enter);
        $this->assertGreaterThan(0, $visitor->leave);
    }

    private function assertWasNotCalled(Counter $visitor): void
    {
        $this->assertSame(0, $visitor->before);
        $this->assertSame(0, $visitor->after);
        $this->assertSame(0, $visitor->enter);
        $this->assertSame(0, $visitor->leave);
    }
}
