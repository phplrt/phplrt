<?php

declare(strict_types=1);

namespace Phplrt\Source\Stream;

use Phplrt\Contracts\Source\Stream\SeekableStreamInterface;

/**
 * A cursor over a string that is already held in memory
 */
final class StringStream implements SeekableStreamInterface
{
    /**
     * @var int<0, max>
     */
    private int $position = 0;

    /**
     * @var int<0, max>
     */
    private readonly int $size;

    public int $offset {
        get => $this->position;
        set {
            // Invariant against the callers not covered by static analysis.
            if ($value < 0) {
                throw new \InvalidArgumentException('Offset cannot be negative');
            }

            $this->position = $value;
        }
    }

    public bool $isEof {
        get => $this->position >= $this->size;
    }

    public function __construct(
        private readonly string $content = '',
    ) {
        $this->size = \strlen($content);
    }

    public function read(int $bytes): string
    {
        // Invariant against the callers not covered by static analysis.
        if ($bytes < 1) { // @phpstan-ignore smaller.alwaysFalse
            throw new \InvalidArgumentException('Number of bytes to read must be greater than 0');
        }

        $result = \substr($this->content, $this->position, $bytes);

        $this->position += \strlen($result);

        return $result;
    }
}
