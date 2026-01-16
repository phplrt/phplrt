<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\IntervalInterface;
use Phplrt\Contracts\Position\PositionInterface;

/**
 * @deprecated since phplrt 3.4 and will be removed in 4.0.
 */
final class Interval implements IntervalInterface
{
    use IntervalFactoryTrait;

    public function __construct(
        private PositionInterface $from,
        private PositionInterface $to,
    ) {
        trigger_deprecation('phplrt/position', '3.4', <<<'MSG'
            Using "%s::class" is deprecated.
            MSG, self::class);
    }

    public function getFrom(): PositionInterface
    {
        return $this->from;
    }

    public function getTo(): PositionInterface
    {
        return $this->to;
    }

    public function getLength(): int
    {
        return \max(0, \abs($this->to->getOffset() - $this->from->getOffset()));
    }
}
