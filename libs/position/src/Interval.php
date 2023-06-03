<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\IntervalInterface;
use Phplrt\Contracts\Position\PositionInterface;

final class Interval implements IntervalInterface
{
    use IntervalFactoryTrait;

    /**
     * @var PositionInterface
     */
    private PositionInterface $from;

    /**
     * @var PositionInterface
     */
    private PositionInterface $to;

    /**
     * @param PositionInterface $from
     * @param PositionInterface $to
     */
    public function __construct(PositionInterface $from, PositionInterface $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * {@inheritDoc}
     */
    public function getFrom(): PositionInterface
    {
        return $this->from;
    }

    /**
     * {@inheritDoc}
     */
    public function getTo(): PositionInterface
    {
        return $this->to;
    }

    /**
     * {@inheritDoc}
     */
    public function getLength(): int
    {
        return \max(0, \abs($this->to->getOffset() - $this->from->getOffset()));
    }
}
