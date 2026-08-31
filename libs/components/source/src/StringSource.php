<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\Exception\NegativeOffsetException;
use Phplrt\Source\Exception\NonPositiveBytesCountException;

/**
 * Implementing a readable object that references a source code as a string value
 *
 * @final please do not inherit from this class
 */
class StringSource extends Readable
{
    public function __construct(
        /**
         * The source code this object is built over
         */
        public readonly string $content = '',
    ) {}

    /**
     * An alias of the {@see $content} property.
     */
    public function getContents(): string
    {
        return $this->content;
    }

    /**
     * @api
     */
    public static function createEmpty(): self
    {
        return new self('');
    }

    /**
     * @api
     */
    public static function createFromString(string $content): self
    {
        return new self($content);
    }

    /**
     * @throws NegativeOffsetException When the offset is negative
     * @throws NonPositiveBytesCountException When the number of bytes is not positive
     */
    public function read(int $offset, int $bytes): string
    {
        // Invariants against the callers not covered by static analysis.
        if ($offset < 0) {
            throw NegativeOffsetException::becauseOffsetIsNegative($offset);
        }

        if ($bytes < 1) {
            throw NonPositiveBytesCountException::becauseBytesCountIsNotPositive($bytes);
        }

        return \substr($this->content, $offset, $bytes);
    }
}
