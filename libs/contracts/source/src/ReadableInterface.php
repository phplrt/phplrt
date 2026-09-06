<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * An arbitrary source code, the data of which can be read in any order.
 *
 * All properties described below SHOULD BE considered actual interface
 * requirements. Their absence in the code is due to support requirements
 * for PHP versions prior to 8.4.
 *
 * @property-read string $content The whole content of the source.
 *
 *                Repeated readings MUST give the same data back.
 *
 *                Reading it throws a {@see SourceExceptionInterface} if the
 *                data of the source cannot be read and/or converted to a string.
 */
interface ReadableInterface
{
    /**
     * Reads at most the given number of bytes located at the given offset,
     * counted in bytes from the beginning of the source.
     *
     * An offset at or beyond the end of the source MUST give an empty string
     * back, and an offset with less data left after it than has been asked for
     * MUST give back everything there is.
     *
     * @param int<0, max> $offset the offset in bytes from the beginning of the
     *        source the reading starts at
     * @param int<1, max> $bytes the maximal number of bytes to read
     * @return string the data that has been read
     * @throws SourceExceptionInterface if the data of the source cannot
     *         be read
     */
    public function read(int $offset, int $bytes): string;
}
