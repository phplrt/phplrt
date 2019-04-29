<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

/**
 * Interface MutablePositionInterface
 */
interface MutablePositionInterface
{
    /**
     * @param int $line
     * @return MutablePositionInterface|$this
     */
    public function withLine(int $line): self;

    /**
     * @param int $column
     * @return MutablePositionInterface|$this
     */
    public function withColumn(int $column): self;
}
