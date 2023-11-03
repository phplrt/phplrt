<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;
use Phplrt\Source\PreferContentReadingInterface;

final class PositionFactory implements PositionFactoryInterface
{
    /**
     * Default chunk size value.
     *
     * @var int<1, max>
     */
    public const DEFAULT_CHUNK_SIZE = 65536;

    /**
     * @var non-empty-string
     */
    protected const LINE_DELIMITER = "\n";

    /**
     * @var int<1, max>
     * @psalm-readonly-allow-private-mutation
     */
    private int $chunkSize = self::DEFAULT_CHUNK_SIZE;

    /**
     * @param int<1, max> $chunkSize The chunk size used while non-blocking
     *        reading the file inside the {@see \Fiber} context.
     */
    public function __construct(
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ) {
        assert($chunkSize >= 1, 'Chunk size must be greater than 0');

        $this->chunkSize = $chunkSize;
    }

    public function createAtStarting(): Position
    {
        return new Position(
            PositionInterface::MIN_OFFSET,
            PositionInterface::MIN_LINE,
            PositionInterface::MIN_COLUMN,
        );
    }

    /**
     * @throws SourceExceptionInterface
     * @throws \FiberError
     * @throws \Throwable
     */
    public function createAtEnding(ReadableInterface $source): Position
    {
        return self::createFromOffset($source, $this->getLength($source));
    }

    /**
     * @throws SourceExceptionInterface
     * @throws \FiberError
     * @throws \Throwable
     */
    public function createFromOffset(
        ReadableInterface $source,
        int $offset = PositionInterface::MIN_OFFSET
    ): Position {
        if ($offset <= PositionInterface::MIN_OFFSET) {
            return self::createAtStarting();
        }

        /**
         * Contains bool {@see true} value in case of fiber support available
         * and the current method was called within the {@see \Fiber} or
         * {@see false} instead.
         */
        $isFiberSupports = \PHP_MAJOR_VERSION >= 8
            && \PHP_MINOR_VERSION >= 1
            && \Fiber::getCurrent() !== null;

        if ($offset > $length = $this->getLength($source)) {
            $offset = $length;
        }

        $stream = $source->getStream();

        // Resulting number of lines in read data.
        $line = 1;

        // Required number of bytes to be read.
        $expected = $offset;

        if ($isFiberSupports) {
            \stream_set_blocking($stream, false);
            \Fiber::suspend();
        }

        do {
            // Read chunk from source to buffer.
            $chunk = (string)\fread($stream, \min($expected, $this->chunkSize));

            // Increase the number of lines by the value of the occurrences of
            // the line breaks in this chunk.
            $line += \substr_count($chunk, self::LINE_DELIMITER);

            if ($isFiberSupports) {
                \Fiber::suspend();
            }

            // Decrement the value of the data required to be read ($expected)
            // by the value of the data already read ($chunk size).
        } while (($expected -= \strlen($chunk)) > 0);

        // Find the last occurrence of line break in the string and reduce the
        // length of the string by this value.
        //
        // The result will be the size of the string after the last occurrence
        // of a line break.
        $column = \strlen($chunk) - (int)\strrpos($chunk, self::LINE_DELIMITER);

        // The first line does not contain any line breaks.
        if ($line === 1) {
            ++$column;
        }

        /** @psalm-suppress InvalidArgument : Column cannot be less than 1 */
        return new Position($offset, $line, $column);
    }

    /**
     * @throws SourceExceptionInterface
     */
    public function createFromPosition(
        ReadableInterface $source,
        int $line = PositionInterface::MIN_LINE,
        int $column = PositionInterface::MIN_COLUMN
    ): Position {
        $line = \max(PositionInterface::MIN_LINE, $line);
        $column = \max(PositionInterface::MIN_COLUMN, $column);

        if ($line === PositionInterface::MIN_LINE
            && $column === PositionInterface::MIN_COLUMN) {
            return self::createAtStarting();
        }

        $stream = $source->getStream();
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
            $offset += $column = \strlen(\reset($lines));
        }

        return new Position($offset, \max(1, $cursor), \max(1, $column));
    }

    /**
     * @return int<0, max>
     * @throws SourceExceptionInterface
     */
    private function getLength(ReadableInterface $source): int
    {
        if ($source instanceof FileInterface && \is_file($source->getPathname())) {
            /** @var int<0, max> */
            return \filesize($source->getPathname());
        }

        // Note that "PreferContentReadingInterface" interface may not exist.
        if ($source instanceof PreferContentReadingInterface) {
            return \strlen($source->getContents());
        }

        $stream = $source->getStream();

        $meta = \stream_get_meta_data($stream);
        if (\stream_is_local($meta['uri']) && \is_readable($meta['uri'])) {
            /** @var int<0, max> */
            return \filesize($meta['uri']);
        }

        \fseek($stream, 0, \SEEK_END);

        /** @var int<0, max> */
        return \ftell($stream);
    }
}
