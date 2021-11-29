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
use Phplrt\Contracts\Source\ReadableInterface;

interface PositionFactoryInterface
{
    /**
     * @param ReadableInterface $source
     * @param positive-int|0 $offset
     * @return PositionInterface
     */
    public function fromOffset(
        ReadableInterface $source,
        int $offset = PositionInterface::MIN_OFFSET
    ): PositionInterface;

    /**
     * @param ReadableInterface $source
     * @param positive-int $line
     * @param positive-int $column
     * @return PositionInterface
     */
    public function fromLineAndColumn(
        ReadableInterface $source,
        int $line = PositionInterface::MIN_LINE,
        int $column = PositionInterface::MIN_COLUMN
    ): PositionInterface;

    /**
     * @return PositionInterface
     */
    public function start(): PositionInterface;

    /**
     * @param ReadableInterface $source
     * @return PositionInterface
     */
    public function end(ReadableInterface $source): PositionInterface;
}
