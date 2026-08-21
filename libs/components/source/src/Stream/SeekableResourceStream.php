<?php

declare(strict_types=1);

namespace Phplrt\Source\Stream;

use Phplrt\Contracts\Source\Stream\SeekableStreamInterface;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotReadableException;

/**
 * A cursor that reads a resource stream in an arbitrary order
 *
 * The position is kept by the cursor itself and is only handed over to the
 * resource right before it is read, so that several cursors are able to share
 * a single resource without interfering with each other.
 */
final class SeekableResourceStream extends ResourceStream implements SeekableStreamInterface
{
    /**
     * @var int<0, max>
     */
    private int $position = 0;

    /**
     * Gets the resource stream URI mentioned in error messages.
     *
     * @var non-empty-string
     */
    private readonly string $name;

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
        /**
         * @throws NotAccessibleException When the stream cannot be rewound
         * @throws NotReadableException When the stream cannot be read
         */
        get {
            $this->rewindToPosition();

            // The byte is read out of the resource without moving the cursor
            // itself, so the reading that follows returns it again.
            return $this->fetch(1) === '';
        }
    }

    /**
     * @param resource $stream The resource stream
     * @throws NotCreatableException When the given value is not a resource stream
     * @throws NotAccessibleException When the resource stream cannot be rewound
     */
    public function __construct(mixed $stream, bool $autoclose = false)
    {
        parent::__construct($stream, $autoclose);

        $metadata = \stream_get_meta_data($stream);

        if ($metadata['seekable'] !== true) {
            throw NotAccessibleException::becauseStreamIsNotSeekable($metadata['uri'] ?? 'php://unknown');
        }

        $uri = $metadata['uri'] ?? '';

        $this->name = $uri === '' ? 'php://unknown' : $uri;
    }

    /**
     * @throws NotAccessibleException When the stream cannot be rewound
     * @throws NotReadableException When the stream cannot be read
     */
    public function read(int $bytes): string
    {
        // Invariant against the callers not covered by static analysis.
        if ($bytes < 1) { // @phpstan-ignore smaller.alwaysFalse
            throw new \InvalidArgumentException('Number of bytes to read must be greater than 0');
        }

        $this->rewindToPosition();

        $result = $this->fetch($bytes);

        $this->position += \strlen($result);

        return $result;
    }

    /**
     * Hands the cursor's position over to the resource in case it is at some
     * other one, which is what reading through another cursor (or peeking at
     * the end of the stream) leaves behind.
     *
     * @throws NotAccessibleException When the stream cannot be rewound
     */
    private function rewindToPosition(): void
    {
        if (\ftell($this->stream) === $this->position) {
            return;
        }

        if (@\fseek($this->stream, $this->position) === -1) {
            throw NotAccessibleException::becauseStreamIsNotSeekable($this->name);
        }
    }
}
