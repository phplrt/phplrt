<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\Exception\InvalidArgumentException;

/**
 * Implementing a readable object that references a source code as a string value
 *
 * @final please do not inherit from this class
 */
class StringSource extends Readable
{
    public string $content {
        get => $this->source;
    }

    public function __construct(
        /**
         * The source code this object is built over
         */
        private readonly string $source = '',
    ) {}

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
     * @throws InvalidArgumentException When the offset is negative or the
     *         number of bytes is not positive
     */
    public function read(int $offset, int $bytes): string
    {
        // Invariants against the callers not covered by static analysis.
        if ($offset < 0) {
            throw InvalidArgumentException::becauseOffsetIsNegative($offset);
        }

        if ($bytes < 1) {
            throw InvalidArgumentException::becauseBytesCountIsNotPositive($bytes);
        }

        return \substr($this->source, $offset, $bytes);
    }
}
