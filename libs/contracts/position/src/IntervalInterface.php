<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

interface IntervalInterface
{
    public function getFrom(): PositionInterface;

    public function getTo(): PositionInterface;

    /**
     * @return int<0, max>
     */
    public function getLength(): int;
}
