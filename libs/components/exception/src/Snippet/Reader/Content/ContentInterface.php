<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet\Reader\Content;

use Phplrt\Exception\Snippet\Exception\SourceNotReadableException;

/**
 * Provides random access to the bytes of the source code.
 */
interface ContentInterface
{
    /**
     * Returns the size of the source code in bytes.
     *
     * @return int<0, max>
     * @throws SourceNotReadableException in case the source cannot be read
     */
    public function getSize(): int;

    /**
     * Returns the position of the last occurrence of the needle starting
     * before the given offset or "null" in case there is no such occurrence.
     *
     * @param non-empty-string $needle
     * @param int<0, max> $offset
     * @return int<0, max>|null
     * @throws SourceNotReadableException in case the source cannot be read
     */
    public function findBefore(string $needle, int $offset): ?int;

    /**
     * Returns the position of the first occurrence of the needle starting at
     * the given offset or after it or "null" in case there is no such
     * occurrence.
     *
     * @param non-empty-string $needle
     * @param int<0, max> $offset
     * @return int<0, max>|null
     * @throws SourceNotReadableException in case the source cannot be read
     */
    public function findAfter(string $needle, int $offset): ?int;

    /**
     * Returns the number of occurrences of the needle starting before the
     * given offset.
     *
     * @param non-empty-string $needle
     * @param int<0, max> $offset
     * @return int<0, max>
     * @throws SourceNotReadableException in case the source cannot be read
     */
    public function countBefore(string $needle, int $offset): int;

    /**
     * Returns the given number of bytes located at the given offset. A range
     * outside the source boundaries is reduced to the available one.
     *
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @throws SourceNotReadableException in case the source cannot be read
     */
    public function read(int $offset, int $length): string;
}
