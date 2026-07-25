<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Exception\Snippet\Exception\SourceNotReadableException;
use Phplrt\Exception\Snippet\Reader\Content\FileContent;
use Phplrt\Exception\Snippet\Reader\Content\StringContent;
use Phplrt\Exception\Snippet\Reader\SourceLineReader;
use Phplrt\Exception\Snippet\SourceLine;

/**
 * Reads a snippet of the source code around the captured (error) fragment.
 */
final readonly class SnippetReader
{
    /**
     * The default number of lines to read before and after the captured ones.
     *
     * @var int<0, max>
     */
    public const int DEFAULT_LINES_AROUND = 1;

    private SourceLineReader $reader;

    public function __construct()
    {
        $this->reader = new SourceLineReader();
    }

    /**
     * Reads the snippet from the given source code.
     *
     * @param int<0, max> $offset the byte offset of the captured fragment
     *        relative to the beginning of the source code. An offsets outside
     *        the source boundaries are reduced to the nearest available one
     * @param int<0, max> $length the size of the captured fragment in bytes. A
     *        fragment ending outside the source code is captured up to its end
     * @param int<0, max> $lines the number of lines to read before and after
     *        the captured ones
     * @return array<int<1, max>, SourceLine> the lines ordered by their numbers
     *         and indexed by them, containing at least one captured line
     */
    public function createFromString(
        string $code,
        int $offset,
        int $length = 0,
        int $lines = self::DEFAULT_LINES_AROUND,
    ): array {
        return $this->reader->read(new StringContent($code), $offset, $length, $lines);
    }

    /**
     * Reads the snippet from the source code of the given file.
     *
     * @param int<0, max> $offset the byte offset of the captured fragment
     *        relative to the beginning of the source code. An offsets outside
     *        the source boundaries are reduced to the nearest available one
     * @param int<0, max> $length the size of the captured fragment in bytes. A
     *        fragment ending outside the source code is captured up to its end
     * @param int<0, max> $lines the number of lines to read before and after
     *        the captured ones
     * @return array<int<1, max>, SourceLine> the lines ordered by their numbers
     *         and indexed by them, containing at least one captured line
     * @throws SourceNotReadableException in case the file cannot be read
     */
    public function createFromPathname(
        string $pathname,
        int $offset,
        int $length = 0,
        int $lines = self::DEFAULT_LINES_AROUND,
    ): array {
        return $this->reader->read(new FileContent($pathname), $offset, $length, $lines);
    }
}
