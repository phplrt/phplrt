<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Source\Factory as FileFactory;
use Phplrt\Source\File;

trait PositionFactoryTrait
{
    /**
     * @param mixed $source
     * @param positive-int $line
     * @param positive-int $column
     * @return PositionInterface
     */
    public static function fromLineAndColumn(
        mixed $source,
        int $line = Position::MIN_LINE,
        int $column = Position::MIN_COLUMN
    ): PositionInterface {
        $factory = FileFactory::getInstance();

        return PositionFactory::getInstance()
            ->fromLineAndColumn($factory->create($source), $line, $column)
        ;
    }

    /**
     * @return PositionInterface
     */
    public static function start(): PositionInterface
    {
        return PositionFactory::getInstance()
            ->start()
        ;
    }

    /**
     * @param mixed $source
     * @return PositionInterface
     */
    public static function end(mixed $source): PositionInterface
    {
        $factory = FileFactory::getInstance();

        return PositionFactory::getInstance()
            ->end($factory->create($source))
        ;
    }

    /**
     * @param mixed $source
     * @param positive-int|0 $offset
     * @return PositionInterface
     */
    public static function fromOffset(mixed $source, int $offset = Position::MIN_OFFSET): PositionInterface
    {
        $factory = FileFactory::getInstance();

        return PositionFactory::getInstance()
            ->fromOffset($factory->create($source), $offset)
        ;
    }
}
