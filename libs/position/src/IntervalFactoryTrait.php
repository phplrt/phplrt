<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\IntervalInterface;
use Phplrt\Contracts\Source\ReadableInterface;

trait IntervalFactoryTrait
{
    /**
     * @param ReadableInterface|string|resource|mixed $source
     * @param int $offset
     * @param int $length
     * @return IntervalInterface
     */
    public static function fromOffset($source, int $offset = 0, int $length = 0): IntervalInterface
    {
        return new Interval(
            Position::fromOffset($source, $offset),
            Position::fromOffset($source, $offset + $length)
        );
    }

    /**
     * @param ReadableInterface|string|resource|mixed $source
     * @param int $line
     * @param int $column
     * @param int $length
     * @return IntervalInterface
     */
    public static function fromPosition($source, int $line = 1, int $column = 1, int $length = 0): IntervalInterface
    {
        return new Interval(
            $from = Position::fromPosition($source, $line, $column),
            Position::fromOffset($source, $from->getOffset() + $length)
        );
    }
}
