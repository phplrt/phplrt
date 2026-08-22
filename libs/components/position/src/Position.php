<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Position\Exception\InvalidArgumentException;

/**
 * Implementing a location inside a source made of the line and the column
 * it points at.
 *
 * A position created with no arguments points at the beginning of any source.
 */
final readonly class Position implements
    PositionInterface,
    \Stringable
{
    /**
     * @throws InvalidArgumentException When the line or the column is less
     *         than the minimal allowed one
     */
    public function __construct(
        /**
         * The number of the source line the position points at.
         *
         * @var int<1, max>
         */
        public int $line = self::MIN_LINE,
        /**
         * The number of the column within its own {@see $line} the position
         * points at.
         *
         * @var int<1, max>
         */
        public int $column = self::MIN_COLUMN,
    ) {
        if ($line < self::MIN_LINE) {
            throw InvalidArgumentException::becauseLineIsOutOfRange($line);
        }

        if ($column < self::MIN_COLUMN) {
            throw InvalidArgumentException::becauseColumnIsOutOfRange($column);
        }
    }

    public function __toString(): string
    {
        return \sprintf('%d:%d', $this->line, $this->column);
    }
}
