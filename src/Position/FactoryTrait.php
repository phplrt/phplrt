<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Position;

/**
 * Trait FactoryTrait
 */
trait FactoryTrait
{
    /**
     * @param string|resource $source
     * @param int $line
     * @param int $column
     * @return Position
     */
    public static function fromPosition($source, int $line = 1, int $column = 1): Position
    {
        \assert($line >= Position::MIN_LINE, 'Line argument should be greater than 1');
        \assert($column >= Position::MIN_COLUMN, 'Column argument should be greater than 1');


        if ($line === Position::MIN_LINE && $column === Position::MIN_COLUMN) {
            return static::start();
        }

        $stream = self::toStream($source);
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
            $last = (string)@\fread($stream, $column - 1);
            $lines = \explode(static::LINE_DELIMITER, $last);

            $offset += $column = \strlen((string)\reset($lines));
        }

        return new Position($offset, $cursor, $column);
    }

    /**
     * @return Position
     */
    public static function start(): Position
    {
        return new Position(Position::MIN_OFFSET, Position::MIN_LINE, Position::MIN_COLUMN);
    }

    /**
     * @param resource|string $source
     * @return resource
     */
    private static function toStream($source)
    {
        switch (true) {
            case \is_string($source):
                \file_put_contents($resource = \fopen('php://memory', 'rb+'), $source);

                return $resource;

            case \is_resource($source):
                return $source;

            default:
                $error = 'A source argument should be a resource or string type, but %s given';
                throw new \TypeError(\sprintf($error, \gettype($source)));
        }
    }

    /**
     * @param $source
     * @return Position
     */
    public static function end($source): Position
    {
        return static::fromOffset($source, \strlen(self::toString($source)));
    }

    /**
     * @param string|resource $source
     * @param int $offset
     * @return Position
     */
    public static function fromOffset($source, int $offset = 0): Position
    {
        \assert($offset >= Position::MIN_OFFSET, 'Offset argument should be greater or equal than 0');

        if ($offset === Position::MIN_OFFSET) {
            return static::start();
        }

        $sources = \substr(self::toString($source), 0, $offset);

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
     * @param resource|string $source
     * @return string
     */
    private static function toString($source): string
    {
        switch (true) {
            case \is_resource($source):
                return \stream_get_contents($source);

            case \is_string($source):
                return $source;

            default:
                $error = 'A source argument should be a resource or string type, but %s given';
                throw new \TypeError(\sprintf($error, \gettype($source)));
        }
    }
}
