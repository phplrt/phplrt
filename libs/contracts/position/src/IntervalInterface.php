<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

interface IntervalInterface
{
    /**
     * @return PositionInterface
     */
    public function getFrom(): PositionInterface;

    /**
     * @return PositionInterface
     */
    public function getTo(): PositionInterface;

    /**
     * @return int<0, max>
     */
    public function getLength(): int;
}
