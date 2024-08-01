<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position;

use Phplrt\Contracts\Source\ReadableInterface;

interface PositionFactoryInterface
{
    /**
     * Creates a {@see PositionInterface} object with zero offset.
     */
    public function createAtStarting(): PositionInterface;

    /**
     * Creates a {@see PositionInterface} object with the end (maximum) position
     * of the passed {@see ReadableInterface} source object.
     */
    public function createAtEnding(ReadableInterface $source): PositionInterface;

    /**
     * Creates a {@see PositionInterface} object based on the passed {@see $offset}
     * and the {@see ReadableInterface} source object.
     *
     * Note that in case of logical errors in the arguments (for example, the
     * maximum number of bytes in the file is less than the {@see $offset}
     * argument passed) such errors SHOULD be automatically corrected.
     *
     * This note does not apply to incorrect arguments (for example, if
     * the {@see $offset} value is string or negative). In this case, any
     * exceptions are allowed, such as {@see \TypeError} for internal PHP
     * behaviour compatibility or {@see \InvalidArgumentException}.
     *
     * The interface does not mark such exceptions in any way through the
     * "@throws" annotation, because this is a design error, breach of contract.
     *
     * @param ReadableInterface $source the source object from which to get the
     *        position object
     * @param int<0, max> $offset offset value in bytes, relative to which the
     *        position will be calculated
     */
    public function createFromOffset(
        ReadableInterface $source,
        int $offset = PositionInterface::MIN_OFFSET,
    ): PositionInterface;

    /**
     * Creates a {@see PositionInterface} object based on the
     * passed {@see $line}, {@see $column} and the {@see ReadableInterface}
     * source object.
     *
     * Note that in case of logical errors in the arguments (for example, the
     * maximum number of lines in the file is less than the {@see $line}
     * argument passed) such errors SHOULD be automatically corrected.
     *
     * This note does not apply to incorrect arguments (for example, if
     * the {@see $line} value is string or non-positive). In this case, any
     * exceptions are allowed, such as {@see \TypeError} for internal PHP
     * behaviour compatibility or {@see \InvalidArgumentException}.
     *
     * The interface does not mark such exceptions in any way through the
     * "@throws" annotation, because this is a design error, breach of contract.
     *
     * @param ReadableInterface $source the source object from which to get the
     *        position object
     * @param int<1, max> $line
     * @param int<1, max> $column
     */
    public function createFromPosition(
        ReadableInterface $source,
        int $line = PositionInterface::MIN_LINE,
        int $column = PositionInterface::MIN_COLUMN,
    ): PositionInterface;
}
