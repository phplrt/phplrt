<?php

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Unit\Stub;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\VisitorInterface;

class Counter implements VisitorInterface
{
    public $before = 0;
    public $after = 0;
    public $enter = 0;
    public $leave = 0;

    public function before(iterable $nodes): ?iterable
    {
        ++$this->before;

        return null;
    }

    public function enter(NodeInterface $node): void
    {
        ++$this->enter;
    }

    public function leave(NodeInterface $node): void
    {
        ++$this->leave;
    }

    public function after(iterable $nodes): ?iterable
    {
        ++$this->after;

        return null;
    }
}
