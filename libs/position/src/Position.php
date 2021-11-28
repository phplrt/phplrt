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

class Position implements PositionInterface
{
    use PositionFactoryTrait;

    /**
     * @var positive-int
     */
    final public const MIN_LINE = PositionInterface::MIN_LINE;

    /**
     * @var positive-int
     */
    final public const MIN_COLUMN = PositionInterface::MIN_COLUMN;

    /**
     * @var positive-int|0
     */
    final public const MIN_OFFSET = PositionInterface::MIN_OFFSET;

    /**
     * @var positive-int|0
     */
    private readonly int $offset;

    /**
     * @var positive-int
     */
    private readonly int $line;

    /**
     * @var positive-int
     */
    private readonly int $column;

    /**
     * @param positive-int|0 $offset
     * @param positive-int $line
     * @param positive-int $column
     * @psalm-suppress MixedAssignment
     */
    public function __construct(
        int $offset = self::MIN_OFFSET,
        int $line = self::MIN_LINE,
        int $column = self::MIN_COLUMN
    ) {
        $this->offset = $offset;
        $this->line = \max($line, static::MIN_LINE);
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
