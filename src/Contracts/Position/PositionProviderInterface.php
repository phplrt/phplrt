<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Position;

/**
 * Interface PositionProviderInterface
 */
interface PositionProviderInterface
{
    /**
     * Returns a position by bytes offset.
     *
     * @param int $offset
     * @return PositionInterface
     */
    public function getPosition(int $offset): PositionInterface;
}
