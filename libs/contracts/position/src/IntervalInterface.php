<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

/**
 * @deprecated since phplrt 3.4 and will be removed in 4.0.
 */
interface IntervalInterface
{
    /**
     * Returns initial position.
     */
    public function getFrom(): PositionInterface;

    /**
     * Returns final position.
     */
    public function getTo(): PositionInterface;

    /**
     * Returns delta from initial and final position in bytes.
     *
     * @return int<0, max>
     */
    public function getLength(): int;
}
