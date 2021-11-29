<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\IntervalInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

final class IntervalFactory implements IntervalFactoryInterface
{
    /**
     * @var IntervalFactoryInterface|null
     */
    private static ?IntervalFactoryInterface $instance = null;

    /**
     * @return IntervalFactoryInterface
     */
    public static function getInstance(): IntervalFactoryInterface
    {
        return self::$instance ??= new self();
    }

    /**
     * @param IntervalFactoryInterface|null $factory
     * @return void
     */
    public static function setInstance(?IntervalFactoryInterface $factory): void
    {
        self::$instance = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function fromOffset(
        ReadableInterface $source,
        int $offset = PositionInterface::MIN_OFFSET,
        int $length = 0
    ): IntervalInterface {
        $position = PositionPositionFactory::getInstance();

        return new Interval(
            $position->fromOffset($source, $offset),
            $position->fromOffset($source, $offset + $length)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fromLineAndColumn(
        ReadableInterface $source,
        int $line = PositionInterface::MIN_LINE,
        int $column = PositionInterface::MIN_COLUMN,
        int $length = 0
    ): IntervalInterface {
        $position = PositionPositionFactory::getInstance();

        return new Interval(
            $from = $position->fromLineAndColumn($source, $line, $column),
            $position->fromOffset($source, $from->getOffset() + $length)
        );
    }
}
