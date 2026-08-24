<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet\Internal;

use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\SourceLine;

/**
 * The fragment of a source projected onto the lines it is read from.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class CapturedFragment
{
    public function __construct(
        /**
         * The fragment of the source, counted in bytes from the beginning
         * of it.
         */
        private FailureInterval $fragment,
        /**
         * The number of the line the fragment starts on.
         *
         * @var int<1, max>
         */
        public int $number,
    ) {}

    /**
     * Returns {@see true} in case the given line holds a part of the fragment.
     */
    public function contains(SourceLine $line): bool
    {
        // The line the fragment starts on is captured no matter how long the
        // fragment is, while a fragment ending right at the beginning of a
        // line leaves that line out.
        return $line->number === $this->number
            || (
                $line->number > $this->number
                && $line->offset < $this->fragment->endsAt
            );
    }

    /**
     * Returns the given line along with the part of the fragment it holds.
     */
    public function capture(SourceLine $line): CapturedSourceLine
    {
        $from = $this->calculateOffset($this->fragment->offset, $line);

        return new CapturedSourceLine($line->number, $line->offset, $line->value, new FailureInterval(
            offset: $from,
            // The fragment may well begin on an earlier line, in which case
            // it captures this one from its very first byte
            length: \max(0, $this->calculateOffset($this->fragment->endsAt, $line) - $from),
        ));
    }

    /**
     * Returns the offset of the given position of the source inside the given
     * line, corrected to the nearest end of it.
     *
     * @param int<0, max> $offset
     * @return int<0, max>
     */
    private function calculateOffset(int $offset, SourceLine $line): int
    {
        return \max(0, \min($offset - $line->offset, \strlen($line->value)));
    }
}
