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
use Phplrt\Source\File;
use Phplrt\Source\Factory as FileFactory;

trait IntervalFactoryTrait
{
    /**
     * @param mixed $source
     * @param positive-int|0 $offset
     * @param positive-int|0 $length
     * @return IntervalInterface
     */
    public static function fromOffset(mixed $source, int $offset = 0, int $length = 0): IntervalInterface
    {
        $factory = FileFactory::getInstance();

        return Factory::getInstance()
            ->fromOffset($factory->create($source), $offset, $length)
        ;
    }

    /**
     * @param mixed $source
     * @param positive-int $line
     * @param positive-int $column
     * @param positive-int|0 $length
     * @return IntervalInterface
     */
    public static function fromLineAndColumn(
        mixed $source,
        int $line = 1,
        int $column = 1,
        int $length = 0
    ): IntervalInterface {
        $factory = FileFactory::getInstance();

        return Factory::getInstance()
            ->fromLineAndColumn($factory->create($source), $line, $column, $length)
        ;
    }
}
