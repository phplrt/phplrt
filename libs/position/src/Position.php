<?php

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
     * @var int<0, max>
     */
    private int $offset;

    /**
     * @var int<1, max>
     */
    private int $line;

    /**
     * @var int<1, max>
     */
    private int $column;

    /**
     * @param int<0, max> $offset
     * @param int<1, max> $line
     * @param int<1, max> $column
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

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getColumn(): int
    {
        return $this->column;
    }
}
