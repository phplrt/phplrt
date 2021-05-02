<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

interface PositionInterface
{
    /**
     * @return positive-int|0
     */
    public function getOffset(): int;

    /**
     * @return positive-int
     */
    public function getLine(): int;

    /**
     * @return positive-int
     */
    public function getColumn(): int;
}
