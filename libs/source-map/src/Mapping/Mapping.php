<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Mapping;

use Phplrt\Contracts\Position\PositionInterface;

class Mapping implements MappingInterface
{
    /**
     * @param positive-int $line
     * @param positive-int $column
     */
    public function __construct(
        public readonly int $line = PositionInterface::MIN_LINE,
        public readonly int $column = PositionInterface::MIN_COLUMN,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * {@inheritDoc}
     */
    public function getColumn(): int
    {
        return $this->column;
    }
}
