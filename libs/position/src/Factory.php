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
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\File;

final class Factory implements FactoryInterface
{
    /**
     * @var string
     */
    final protected const LINE_DELIMITER = "\n";

    /**
     * @var FactoryInterface|null
     */
    private static ?FactoryInterface $instance = null;

    /**
     * @return FactoryInterface
     */
    public static function getInstance(): FactoryInterface
    {
        return self::$instance ??= new self();
    }

    /**
     * @param FactoryInterface|null $factory
     * @return void
     */
    public static function setInstance(?FactoryInterface $factory): void
    {
        self::$instance = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function fromLineAndColumn(
        ReadableInterface $source,
        int $line = Position::MIN_LINE,
        int $column = Position::MIN_COLUMN
    ): PositionInterface {
        assert($line >= Position::MIN_LINE, new \InvalidArgumentException(
            'Line argument should be greater than ' . Position::MIN_LINE
        ));

        assert($column >= Position::MIN_COLUMN, new \InvalidArgumentException(
            'Column argument should be greater than ' . Position::MIN_COLUMN
        ));

        if ($line === Position::MIN_LINE && $column === Position::MIN_COLUMN) {
            return $this->start();
        }

        $stream = File::new($source)->getStream();
        $offset = $cursor = 0;

        //
        // Calculate the number of bytes that the transmitted
        // number of lines takes.
        //
        while (!\feof($stream) && $cursor++ + 1 < $line) {
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
            $lines = \explode(self::LINE_DELIMITER, $last);
            $offset += $column = \strlen((string)\reset($lines));
        }

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress PossiblyInvalidArgument
         */
        return new Position($offset, $cursor, $column);
    }

    /**
     * @return PositionInterface
     */
    public function start(): PositionInterface
    {
        return new Position(
            offset: PositionInterface::MIN_OFFSET,
            line: PositionInterface::MIN_LINE,
            column: PositionInterface::MIN_COLUMN,
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function end(ReadableInterface $source): PositionInterface
    {
        return $this->fromOffset($source, $this->length($source));
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotAccessibleException
     * @throws \RuntimeException
     *
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedOperand
     */
    public function fromOffset(
        ReadableInterface $source,
        int $offset = Position::MIN_OFFSET
    ): PositionInterface {
        assert($offset >= Position::MIN_OFFSET, new \InvalidArgumentException(
            'Offset argument should be greater than ' . Position::MIN_OFFSET
        ));

        if ($offset <= Position::MIN_OFFSET) {
            return $this->start();
        }

        if ($offset > $this->length($source)) {
            return $this->end($source);
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
        $lines = \substr_count($sources, self::LINE_DELIMITER, 0, $offset) + 1;

        //
        // Go through the last line before the first occurrence
        // of line break. This value will be a column.
        //
        /** @psalm-suppress MixedAssignment */
        for ($i = $offset, $column = 1; $i > 0 && $sources[$i - 1] !== self::LINE_DELIMITER; --$i) {
            ++$column;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return new Position($offset, $lines, $column);
    }

    /**
     * @param ReadableInterface $source
     * @return positive-int|0
     * @throws NotAccessibleException
     * @throws \RuntimeException
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    private function length(ReadableInterface $source): int
    {
        return \strlen($source->getContents());
    }
}
