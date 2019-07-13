<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception\MutableException;

use Phplrt\Contracts\Exception\MutableException\MutablePositionInterface;
use Phplrt\Exception\ColumnPositionTrait;

/**
 * Trait MutablePositionTrait
 *
 * @mixin MutablePositionInterface
 * @mixin \Exception
 */
trait MutablePositionTrait
{
    use ColumnPositionTrait;

    /**
     * @param int $line
     * @return MutablePositionInterface|$this
     */
    public function withLine(int $line): MutablePositionInterface
    {
        $this->line = \max(1, $line);

        return $this;
    }

    /**
     * @param int $column
     * @return MutablePositionInterface|$this
     */
    public function withColumn(int $column): MutablePositionInterface
    {
        $this->column = \max(1, $column);

        return $this;
    }
}
