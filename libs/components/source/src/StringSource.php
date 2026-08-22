<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FiniteStreamInterface;
use Phplrt\Contracts\Source\SeekableStreamInterface;
use Phplrt\Source\Exception\InvalidArgumentException;

/**
 * Implementing a readable object that references a source code as a string value
 *
 * @final please do not inherit from this class
 */
class StringSource extends Readable implements FiniteStreamInterface, SeekableStreamInterface
{
    /**
     * @var int<0, max>
     */
    private int $position = 0;

    public string $content {
        get {
            $result = \substr($this->source, $this->position);

            $this->position = \strlen($this->source);

            return $result;
        }
    }

    /**
     * @var int<0, max>
     */
    public int $size {
        get => \strlen($this->source);
    }

    /**
     * @var int<0, max>
     */
    public int $offset {
        get => $this->position;
        set {
            // Invariant against the callers not covered by static analysis.
            if ($value < 0) { // @phpstan-ignore smaller.alwaysFalse
                throw InvalidArgumentException::becauseOffsetIsNegative($value);
            }

            $this->position = $value;
        }
    }

    public bool $isEof {
        get => $this->position >= $this->size;
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
     * @throws InvalidArgumentException When the number of bytes is not positive
     */
    public function read(int $bytes): string
    {
        // Invariant against the callers not covered by static analysis.
        if ($bytes < 1) { // @phpstan-ignore smaller.alwaysFalse
            throw InvalidArgumentException::becauseBytesCountIsNotPositive($bytes);
        }

        $result = \substr($this->source, $this->position, $bytes);

        $this->position += \strlen($result);

        return $result;
    }
}
