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

final class Position implements PositionInterface
{
    use PositionFactoryTrait;

    /**
     * @var non-empty-string
     */
    protected const LINE_DELIMITER = "\n";

    /**
     * @var positive-int|0
     */
    private int $offset;

    /**
     * @var positive-int
     */
    private int $line;

    /**
     * @var positive-int
     */
    private int $column;

    /**
     * @param positive-int|0 $offset
     * @param positive-int $line
     * @param positive-int $column
     */
    public function __construct(
        int $offset = self::MIN_OFFSET,
        int $line = self::MIN_LINE,
        int $column = self::MIN_COLUMN
    ) {
        $this->offset = $offset;
        $this->line   = \max($line, static::MIN_LINE);
        $this->column = \max($column, static::MIN_COLUMN);
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(): int
    {
        return $this->offset;
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
