<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\IntervalInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;

/**
 * @deprecated since phplrt 3.4 and will be removed in 4.0.
 */
trait IntervalFactoryTrait
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     *
     * @throws SourceExceptionInterface
     */
    public static function fromOffset($source, int $offset = 0, int $length = 0): IntervalInterface
    {
        return new Interval(
            Position::fromOffset($source, $offset),
            Position::fromOffset($source, $offset + $length)
        );
    }

    /**
     * @param int<1, max> $line
     * @param int<1, max> $column
     * @param int<0, max> $length
     *
     * @throws SourceExceptionInterface
     */
    public static function fromPosition($source, int $line = 1, int $column = 1, int $length = 0): IntervalInterface
    {
        return new Interval(
            $from = Position::fromPosition($source, $line, $column),
            Position::fromOffset($source, $from->getOffset() + $length)
        );
    }
}
