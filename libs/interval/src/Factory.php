<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Interval;

use Phplrt\Contracts\Interval\IntervalInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Factory as PositionFactory;

final class Factory implements FactoryInterface
{
    /**
     * @var FactoryInterface|null
     */
    private static ?FactoryInterface $instance = null;

    /**
     * @return FactoryInterface
     */
    public static function getInstance(): FactoryInterface
    {
        return self::$instance ??= new self();
    }

    /**
     * @param FactoryInterface|null $factory
     * @return void
     */
    public static function setInstance(?FactoryInterface $factory): void
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
        $position = PositionFactory::getInstance();

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
        $position = PositionFactory::getInstance();

        return new Interval(
            $from = $position->fromLineAndColumn($source, $line, $column),
            $position->fromOffset($source, $from->getOffset() + $length)
        );
    }
}
