<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Source\File;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotAccessibleException;

/**
 * Trait FactoryTrait
 */
trait FactoryTrait
{
    /**
     * @param ReadableInterface|string|resource|mixed $source
     * @param int $line
     * @param int $column
     * @return PositionInterface
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public static function fromPosition($source, int $line = 1, int $column = 1): PositionInterface
    {
        \assert($line >= Position::MIN_LINE, 'Line argument should be greater than 1');
        \assert($column >= Position::MIN_COLUMN, 'Column argument should be greater than 1');

        if ($line === Position::MIN_LINE && $column === Position::MIN_COLUMN) {
            return static::start();
        }

        $stream = File::new($source)->getStream();
        $offset = $cursor = 0;

        //
        // Calculate the number of bytes that the transmitted
        // number of lines takes.
        //
        while (! \feof($stream) && $cursor++ + 1 < $line) {
            $offset += \strlen((string)\fgets($stream));
        }

        //
        // In the case that the column is not the first one, then
        // we calculate the number of bytes contained in the very
        // last source line not exceeding the size of the transmitted
        // column.
        //
        if ($column !== 1) {
            $last              = (string)@\fread($stream, $column - 1);
            $lines             = \explode(static::LINE_DELIMITER, $last);
            $offset += $column = \strlen((string)\reset($lines));
        }

        return new Position($offset, $cursor, $column);
    }

    /**
     * @return PositionInterface
     */
    public static function start(): PositionInterface
    {
        return new Position(Position::MIN_OFFSET, Position::MIN_LINE, Position::MIN_COLUMN);
    }

    /**
     * @param ReadableInterface|string|resource|mixed $source
     * @return Position
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public static function end($source): PositionInterface
    {
        $source = File::new($source);

        return static::fromOffset($source, self::length($source));
    }

    /**
     * @param ReadableInterface|string|resource|mixed $source
     * @param int $offset
     * @return PositionInterface
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public static function fromOffset($source, int $offset = 0): PositionInterface
    {
        if ($offset <= Position::MIN_OFFSET) {
            return static::start();
        }

        $source = File::new($source);

        if ($offset > self::length($source)) {
            return static::end($source);
        }

        $sources = \fread($source->getStream(), $offset);

        //
        // Format the offset so that it does not exceed the allowable text
        // size and is not less than zero.
        //
        $offset = \max(0, \min(\strlen($sources), $offset));

        //
        // The number of occurrences of lines found in the desired text slice.
        //
        $lines = \substr_count($sources, static::LINE_DELIMITER, 0, $offset) + 1;

        //
        // Go through the last line before the first occurrence
        // of line break. This value will be a column.
        //
        for ($i = $offset, $column = 1; $i > 0 && $sources[$i - 1] !== static::LINE_DELIMITER; --$i) {
            ++$column;
        }

        return new Position($offset, $lines, $column);
    }

    /**
     * @param ReadableInterface|string|resource|mixed $source
     * @return int
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    private static function length($source): int
    {
        return \strlen(File::new($source)->getContents());
    }
}
