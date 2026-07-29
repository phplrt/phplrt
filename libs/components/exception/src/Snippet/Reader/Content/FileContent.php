<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet\Reader\Content;

use Phplrt\Exception\Snippet\Exception\SourceNotReadableException;

/**
 * The source code available as a file.
 */
final class FileContent implements ContentInterface
{
    /**
     * The default number of bytes loaded at once while searching.
     *
     * @var int<1, max>
     */
    public const int DEFAULT_CHUNK_SIZE = 8192;

    /**
     * The default maximum number of bytes loaded at once while scanning the
     * source code sequentially.
     *
     * @var int<1, max>
     */
    public const int DEFAULT_SLICE_SIZE = 1_048_576;

    /**
     * @var int<0, max>|null
     */
    private ?int $size = null;

    private ?string $buffer = null;

    /**
     * @var int<0, max>
     */
    private int $bufferOffset = 0;

    public function __construct(
        private readonly string $pathname,
        /**
         * The number of bytes loaded at once while searching.
         *
         * @var int<1, max>
         */
        private readonly int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        /**
         * The maximum number of bytes loaded at once while scanning the source
         * code sequentially.
         *
         * @var int<1, max>
         */
        private readonly int $sliceSize = self::DEFAULT_SLICE_SIZE,
    ) {}

    public function getSize(): int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        // Any additional check (like an "is_file" one) costs more than the
        // reading itself, while an unreadable file is detected anyway.
        $size = @\filesize($this->pathname);

        if ($size === false) {
            throw SourceNotReadableException::becauseFileIsNotReadable($this->pathname);
        }

        return $this->size = \max(0, $size);
    }

    public function findBefore(string $needle, int $offset): ?int
    {
        $size = $this->getSize();
        $offset = \max(0, \min($offset, $size));

        if ($offset === 0) {
            return null;
        }

        $limit = \min($offset + \strlen($needle) - 1, $size);

        for ($width = $this->chunkSize;; $width *= 2) {
            $from = \max(0, $offset - $width);
            $chunk = $this->load($from, $limit);

            // A negative offset limits the search to the occurrences starting
            // at the corresponding position or before it.
            $result = \strrpos($chunk, $needle, $offset - $from - \strlen($chunk) - 1);

            if ($result !== false) {
                return \max(0, $from + $result);
            }

            if ($from === 0) {
                return null;
            }
        }
    }

    public function findAfter(string $needle, int $offset): ?int
    {
        $size = $this->getSize();
        $offset = \max(0, $offset);

        if ($offset >= $size) {
            return null;
        }

        for ($width = $this->chunkSize;; $width *= 2) {
            $to = \min($offset + $width, $size);
            $result = \strpos($this->load($offset, $to), $needle);

            if ($result !== false) {
                return $offset + $result;
            }

            if ($to === $size) {
                return null;
            }
        }
    }

    public function countBefore(string $needle, int $offset): int
    {
        $size = $this->getSize();
        $end = \max(0, \min($offset, $size));
        $width = \strlen($needle) - 1;

        if ($this->canLoadAtOnce()) {
            return \substr_count($this->load(0, $size), $needle, 0, \min($end + $width, $size));
        }

        $result = 0;
        $from = 0;

        while (($remaining = $end - $from) > 0) {
            $length = \min($this->sliceSize, $remaining);

            // The needle located at the boundary of two slices belongs to the
            // one containing its first byte.
            $slice = $this->fetch($from, \min($from + $length + $width, $size));

            $result += \substr_count($slice, $needle, 0, \min(\strlen($slice), $length + $width));
            $from += $length;
        }

        return $result;
    }

    public function read(int $offset, int $length): string
    {
        $size = $this->getSize();
        $from = \max(0, \min($offset, $size));

        return $this->load($from, \min($from + \max(0, $length), $size));
    }

    /**
     * @param int<0, max> $from
     * @param int<0, max> $to
     * @throws SourceNotReadableException
     */
    private function load(int $from, int $to): string
    {
        $buffer = $this->buffer;
        $offset = $this->bufferOffset;

        if ($buffer === null || $from < $offset || $to > $offset + \strlen($buffer)) {
            if ($this->canLoadAtOnce()) {
                $start = 0;
                $end = $this->getSize();
            } elseif ($buffer === null) {
                $start = $from;
                $end = $to;
            } else {
                $start = \min($from, $offset);
                $end = \max($to, $offset + \strlen($buffer));
            }

            $this->buffer = $buffer = $this->fetch($start, $end);
            $this->bufferOffset = $offset = $start;
        }

        return \substr($buffer, $from - $offset, $to - $from);
    }

    /**
     * @throws SourceNotReadableException
     */
    private function canLoadAtOnce(): bool
    {
        // Reading a small file as a whole costs less than reading it by chunks.
        return $this->getSize() <= $this->sliceSize;
    }

    /**
     * @param int<0, max> $from
     * @param int<0, max> $to
     * @throws SourceNotReadableException
     */
    private function fetch(int $from, int $to): string
    {
        $pathname = $this->pathname;

        $content = @\file_get_contents($pathname, false, null, $from, \max(0, $to - $from));

        if ($content === false || !\is_file($pathname)) {
            throw SourceNotReadableException::becauseFileIsNotReadable($pathname);
        }

        return $content;
    }
}
