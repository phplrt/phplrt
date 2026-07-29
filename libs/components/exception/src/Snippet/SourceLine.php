<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet;

/**
 * A single line of the source code.
 */
readonly class SourceLine
{
    public function __construct(
        /**
         * The line number, starting from 1.
         *
         * @var int<1, max>
         */
        public int $number,
        /**
         * The byte offset of the first character of the line relative to the
         * beginning of the source.
         *
         * @var int<0, max>
         */
        public int $offset,
        /**
         * The content of the line without the trailing line delimiter.
         */
        public string $value,
    ) {}

    public function __toString(): string
    {
        return $this->value;
    }
}
