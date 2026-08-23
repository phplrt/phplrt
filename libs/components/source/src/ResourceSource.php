<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\Exception\InvalidArgumentException;
use Phplrt\Source\Exception\LogicException;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotReadableException;

/**
 * Implementing a readable object that references to a resource stream
 *
 * The source begins where the stream has been left at the moment it has been
 * given away, so a stream that has already been read in part is the source of
 * what is left in it.
 *
 * A stream that cannot be rewound keeps in memory everything it has given
 * away, which is what makes such a source readable in an arbitrary order.
 *
 * @phpstan-type StreamMetaType array{
 *     timed_out: bool,
 *     blocked: bool,
 *     eof: bool,
 *     unread_bytes: int,
 *     stream_type: string,
 *     wrapper_type: string,
 *     wrapper_data: mixed,
 *     mode: string,
 *     seekable: bool,
 *     uri?: string,
 *     ...
 * }
 *
 * @final please do not inherit from this class
 */
class ResourceSource extends Readable
{
    /**
     * The number of bytes taken out of a stream at once while it is being
     * kept in memory.
     *
     * @var int<1, max>
     */
    private const int CHUNK_SIZE = 65536;

    /**
     * The position of the stream this source begins at.
     *
     * @var int<0, max>
     */
    private readonly int $base;

    /**
     * The data of a stream that cannot be rewound.
     */
    private string $memory = '';

    /**
     * Whether the stream that cannot be rewound has been read to its end.
     */
    private bool $isMemoryComplete = false;

    /**
     * The resource this source reads from.
     *
     * @var resource
     */
    private mixed $resource {
        /**
         * @throws NotCreatableException When the resource has been closed from the outside
         */
        get {
            if (!\is_resource($this->stream)) {
                throw NotCreatableException::becauseSourceIs('closed resource');
            }

            return $this->stream;
        }
    }

    public string $content {
        /**
         * @throws NotCreatableException When the stream has been closed from the outside
         * @throws NotReadableException When the stream cannot be read
         */
        get {
            if (!$this->isSeekable) {
                $this->remember(0, \PHP_INT_MAX);

                return $this->memory;
            }

            $this->seek(0);

            return $this->takeRest();
        }
    }

    /**
     * Gets stream URI string (can be optional)
     *
     * @var non-empty-string|null
     */
    public readonly ?string $uri;

    /**
     * Gets the stream access mode (e.g., "rb", "rb+", "w", etc.)
     *
     * @var non-empty-string
     */
    public readonly string $mode;

    /**
     * Gets {@see true} if the stream is local
     */
    public readonly bool $isLocal;

    /**
     * Gets {@see true} if the stream supports offset (seek/rewind) changes
     */
    public readonly bool $isSeekable;

    /**
     * @throws NotCreatableException When the given value is not a resource stream
     * @throws NotReadableException When the resource stream is not open for reading
     */
    public function __construct(
        /**
         * @var resource
         */
        private readonly mixed $stream,
        /**
         * Whether the resource stream is closed along with this object.
         */
        private readonly bool $autoclose = false,
    ) {
        if (!\is_resource($stream)) {
            throw NotCreatableException::becauseSourceIsInvalid($stream);
        }

        if (\get_resource_type($stream) !== 'stream') {
            throw NotCreatableException::becauseSourceIs('non-stream resource');
        }

        $metadata = \stream_get_meta_data($stream);

        $this->uri = $this->findUriFromMetadata($metadata);
        $this->mode = $this->getModeFromMetadata($metadata);
        $this->isLocal = $this->getIsLocalInfoFromMetadata($metadata);
        $this->isSeekable = $metadata['seekable'];

        if (!$this->isReadableMode($this->mode)) {
            throw NotReadableException::becauseStreamIsNotReadable($this->uri ?? $this->mode);
        }

        $this->base = $this->isSeekable ? \max(0, (int) @\ftell($stream)) : 0;
    }

    /**
     * @api
     *
     * @param resource $resource
     */
    public static function createFromResource(mixed $resource): self
    {
        return new self($resource, false);
    }

    /**
     * @throws InvalidArgumentException When the offset is negative or the
     *         number of bytes is not positive
     * @throws NotCreatableException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read
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

        if (!$this->isSeekable) {
            $this->remember($offset, $bytes);

            return \substr($this->memory, $offset, $bytes);
        }

        $this->seek($offset);

        return $this->fetch($bytes);
    }

    /**
     * Takes the data of a stream that cannot be rewound out of it and keeps
     * it, up to the position the given fragment ends at.
     *
     * @param int<0, max> $offset
     * @param int<1, max> $bytes
     * @throws NotCreatableException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read
     */
    private function remember(int $offset, int $bytes): void
    {
        // A fragment reaching beyond what an integer holds is the whole of
        // whatever the stream has.
        $required = $offset > \PHP_INT_MAX - $bytes
            ? \PHP_INT_MAX
            : $offset + $bytes;

        while (!$this->isMemoryComplete && ($rest = $required - \strlen($this->memory)) >= 1) {
            $chunk = $this->fetch(\min(self::CHUNK_SIZE, $rest));

            if ($chunk === '') {
                $this->isMemoryComplete = true;

                return;
            }

            $this->memory .= $chunk;
        }
    }

    /**
     * Reads everything the stream has left in it.
     *
     * @throws NotCreatableException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read
     */
    private function takeRest(): string
    {
        \error_clear_last();

        $result = @\stream_get_contents($this->resource);

        if ($result === false) {
            throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
        }

        return $result;
    }

    /**
     * Reads the given number of bytes out of the stream at the position it
     * currently is at.
     *
     * @param int<1, max> $bytes
     * @throws NotCreatableException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read
     */
    private function fetch(int $bytes): string
    {
        \error_clear_last();

        $result = @\fread($this->resource, $bytes);

        if ($result === false) {
            throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
        }

        return $result;
    }

    /**
     * Moves the stream to the given position of this source, which the stream
     * is not necessarily left at: reading through it moves it elsewhere.
     *
     * @param int<0, max> $offset
     * @throws NotCreatableException When the resource has been closed from the outside
     */
    private function seek(int $offset): void
    {
        $expected = $offset > \PHP_INT_MAX - $this->base
            ? \PHP_INT_MAX
            : $this->base + $offset;

        if (\ftell($this->resource) !== $expected) {
            @\fseek($this->resource, $expected);
        }
    }

    /**
     * Tells whether the given access mode allows reading
     *
     * @param non-empty-string $mode
     */
    private function isReadableMode(string $mode): bool
    {
        return \str_contains($mode, 'r')
            || \str_contains($mode, '+');
    }

    /**
     * Extracts "local" bool flag stream information from metadata
     *
     * @param StreamMetaType $metadata Stream metadata array
     * @return bool {@see true} if the stream is local, {@see false} otherwise
     */
    private function getIsLocalInfoFromMetadata(array $metadata): bool
    {
        return isset($metadata['uri'])
            && \stream_is_local($metadata['uri']);
    }

    /**
     * Extracts stream mode from metadata
     *
     * @param StreamMetaType $metadata Stream metadata array
     * @return non-empty-string The stream access mode
     */
    private function getModeFromMetadata(array $metadata): string
    {
        $mode = $metadata['mode'];

        if ($mode === '') {
            return 'rb';
        }

        return $mode;
    }

    /**
     * Extracts stream URI from metadata
     *
     * @param StreamMetaType $metadata Stream metadata array
     * @return non-empty-string|null The stream URI or {@see null} if not available
     */
    private function findUriFromMetadata(array $metadata): ?string
    {
        $uri = $metadata['uri'] ?? null;

        if ($uri === null || $uri === '') {
            return null;
        }

        return $uri;
    }

    /**
     * Serializes the stream object
     *
     * @return array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     *     base: int<0, max>,
     * }
     * @throws LogicException When the stream does not have a URI
     */
    public function __serialize(): array
    {
        if ($this->uri === null) {
            throw LogicException::becauseStreamHasNoUri($this->mode);
        }

        return [
            'uri' => $this->uri,
            'mode' => $this->mode,
            'base' => $this->base,
        ];
    }

    /**
     * Unserializes the stream object
     *
     * @param array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     *     base: int<0, max>,
     *     ...
     * } $data
     * @throws NotReadableException When the stream cannot be opened
     * @throws LogicException When the stream cannot be moved to the position
     *         the source begins at
     */
    public function __unserialize(array $data): void
    {
        \error_clear_last();

        $stream = @\fopen($data['uri'], $data['mode']);

        if ($stream === false) {
            throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
        }

        $this->stream = $stream;
        $this->uri = $data['uri'];
        $this->mode = $data['mode'];
        $this->isLocal = \stream_is_local($data['uri']);
        $this->isSeekable = \stream_get_meta_data($stream)['seekable'];

        if ($data['base'] > 0 && !$this->isSeekable) {
            throw LogicException::becauseStreamIsNotSeekable($data['uri']);
        }

        $this->base = $data['base'];

        // The stream has been opened here rather than passed in, so this
        // object is the one to close it.
        $this->autoclose = true;
    }

    public function __destruct()
    {
        if ($this->autoclose && \is_resource($this->stream)) {
            \fclose($this->stream);
        }
    }
}
