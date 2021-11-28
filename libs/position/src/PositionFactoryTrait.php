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
        return Factory::getInstance()
            ->fromLineAndColumn(File::new($source), $line, $column)
        ;
    }

    /**
     * @return PositionInterface
     */
    public static function start(): PositionInterface
    {
        return Factory::getInstance()
            ->start()
        ;
    }

    /**
     * @param mixed $source
     * @return PositionInterface
     */
    public static function end(mixed $source): PositionInterface
    {
        return Factory::getInstance()
            ->end(File::new($source))
        ;
    }

    /**
     * @param mixed $source
     * @param int $offset
     * @return PositionInterface
     */
    public static function fromOffset(mixed $source, int $offset = Position::MIN_OFFSET): PositionInterface
    {
        return Factory::getInstance()
            ->fromOffset(File::new($source), $offset)
        ;
    }
}
