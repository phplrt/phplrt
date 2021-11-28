<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap;

use Phplrt\Contracts\Position\PositionInterface;

abstract class Mapping implements PositionInterface
{
    /**
     * @param PositionInterface $position
     */
    public function __construct(
        protected readonly PositionInterface $position,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(): int
    {
        return $this->position->getOffset();
    }

    /**
     * {@inheritDoc}
     */
    public function getLine(): int
    {
        return $this->position->getLine();
    }

    /**
     * {@inheritDoc}
     */
    public function getColumn(): int
    {
        return $this->position->getColumn();
    }
}
