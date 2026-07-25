<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet\Reader;

use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\Exception\SourceNotReadableException;
use Phplrt\Exception\Snippet\Reader\Content\ContentInterface;
use Phplrt\Exception\Snippet\SourceLine;

/**
 * Reads the lines of the source code around the captured (error) fragment.
 */
final readonly class SourceLineReader
{
    /**
     * @var non-empty-string
     */
    private const string DELIMITER_ANCHOR = "\n";

    /**
     * @var non-empty-string
     */
    private const string DELIMITER_EXTRA = "\r";

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @param int<0, max> $lines
     * @return array<int<1, max>, SourceLine>
     * @throws SourceNotReadableException
     */
    public function read(ContentInterface $content, int $offset, int $length, int $lines): array
    {
        $size = $content->getSize();
        $offset = \max(0, \min($offset, $size));
        $end = $offset + \max(0, \min($length, \PHP_INT_MAX - $offset));
        $lines = \max(0, $lines);

        $anchor = $content->findBefore(self::DELIMITER_ANCHOR, $offset);
        $start = $anchor === null ? 0 : $anchor + 1;
        $number = $content->countBefore(self::DELIMITER_ANCHOR, $start) + 1;

        $result = $this->readBefore($content, $start, $number, $lines);

        $current = $number;
        $trailing = $lines;

        while (true) {
            // A fragment ending right at the beginning of a line does not
            // capture this line.
            $captured = $current === $number || $start < $end;

            if (!$captured && $trailing-- === 0) {
                break;
            }

            $anchor = $content->findAfter(self::DELIMITER_ANCHOR, $start);
            $value = $this->readLine($content, $start, $anchor);

            $result[$current] = $captured
                ? new CapturedSourceLine(
                    $current,
                    $start,
                    $value,
                    $this->calculateColumn($offset, $start, $value),
                    $this->calculateColumn($end, $start, $value),
                )
                : new SourceLine($current, $start, $value);

            if ($anchor === null) {
                break;
            }

            $start = $anchor + 1;
            ++$current;
        }

        return $result;
    }

    /**
     * @param int<0, max> $start
     * @param int<1, max> $number
     * @param int<0, max> $lines
     * @return array<int<1, max>, SourceLine>
     * @throws SourceNotReadableException
     */
    private function readBefore(ContentInterface $content, int $start, int $number, int $lines): array
    {
        $result = [];

        for ($i = 1; $i <= $lines && $start > 0; ++$i) {
            $anchor = $start - 1;
            $previous = $content->findBefore(self::DELIMITER_ANCHOR, $anchor);
            $start = $previous === null ? 0 : $previous + 1;

            $current = \max(1, $number - $i);

            $result[$current] = new SourceLine($current, $start, $this->readLine($content, $start, $anchor));
        }

        return \array_reverse($result, true);
    }

    /**
     * @param int<0, max> $start
     * @param int<0, max>|null $anchor
     * @throws SourceNotReadableException
     */
    private function readLine(ContentInterface $content, int $start, ?int $anchor): string
    {
        if ($anchor === null) {
            return $content->read($start, \max(0, $content->getSize() - $start));
        }

        $value = $content->read($start, \max(0, $anchor - $start));

        return \str_ends_with($value, self::DELIMITER_EXTRA)
            ? \substr($value, 0, -1)
            : $value;
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $start
     * @return int<1, max>
     */
    private function calculateColumn(int $offset, int $start, string $value): int
    {
        return \max(1, \min($offset - $start + 1, \strlen($value) + 1));
    }
}
