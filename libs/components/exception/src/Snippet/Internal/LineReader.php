<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet\Internal;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Snippet\SourceLine;

/**
 * Reads a source line by line, a chunk of bytes at a time.
 *
 * A line ends wherever its delimiter is, and the data left after the last
 * delimiter is the line the source ends with. Lines are separated by "\n",
 * and the "\r" of a "\r\n" delimiter belongs to the delimiter rather than to
 * the line.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 *
 * @readonly
 */
final class LineReader
{
    /**
     * @var non-empty-string
     */
    private const DELIMITER_ANCHOR = "\n";

    /**
     * @var non-empty-string
     */
    private const DELIMITER_EXTRA = "\r";

    public function __construct(
        /**
         * The number of bytes read at once.
         *
         * @var int<1, max>
         */
        private readonly int $chunkSize,
    ) {}

    /**
     * Returns the lines of the given source, starting at the given offset.
     *
     * @param int<0, max> $from the offset the first line starts at
     * @param int<1, max> $number the number of the first line
     * @return iterable<mixed, SourceLine>
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    public function read(ReadableInterface $source, int $from, int $number): iterable
    {
        $buffer = '';
        $offset = $from;
        $at = $from;

        while (($chunk = $source->read($at, $this->chunkSize)) !== '') {
            $at += \strlen($chunk);
            $buffer .= $chunk;

            // The line the buffer ends with is not closed by a delimiter yet,
            // so it waits for the data that follows it.
            $closed = \explode(self::DELIMITER_ANCHOR, $buffer);
            $buffer = \array_pop($closed);

            foreach ($closed as $value) {
                yield new SourceLine($number, $offset, $this->trim($value));

                $number = \max(SourceLine::MIN_NUMBER, $number + 1);
                $offset = \max(0, $offset + \strlen($value) + 1);
            }
        }

        // The source ends without a delimiter, so whatever is left of it is
        // the last line, the "\r" of which belongs to the line rather than
        // closes it.
        yield new SourceLine($number, $offset, $buffer);
    }

    /**
     * Returns the line without the part of the delimiter that closes it.
     */
    private function trim(string $value): string
    {
        return \str_ends_with($value, self::DELIMITER_EXTRA)
            ? \substr($value, 0, -1)
            : $value;
    }
}
