<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Internal;

/**
 * A part of the source code line fitting into the available width.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class LinePart
{
    public function __construct(
        /**
         * The characters of the line contained in the part.
         */
        public string $value,
        /**
         * The offset of the first character of the part inside the line.
         *
         * @var int<0, max>
         */
        public int $from,
        /**
         * The offset following the last character of the part inside the line.
         *
         * @var int<0, max>
         */
        public int $to,
        /**
         * The number of columns preceding the characters of the part.
         *
         * @var int<0, max>
         */
        public int $indent,
        /**
         * The line is continued by the next part.
         */
        public bool $continued,
    ) {}

    /**
     * Returns "true" in case the given offset of the line points inside
     * the part.
     *
     * @param int<0, max> $offset
     */
    public function contains(int $offset): bool
    {
        return $offset >= $this->from
            && ($offset < $this->to || !$this->continued);
    }
}
