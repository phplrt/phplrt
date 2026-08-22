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
 * A stream that can be rewound is the source of everything it holds, no matter
 * where it has been left at. The one that cannot be rewound is the source of
 * what is still left in it, and is therefore readable only once.
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
     * @var int<0, max>
     */
    private int $position = 0;

    /**
     * The byte that has already been read out of the resource in order to
     * find out whether the end has been reached.
     *
     * The resource is left at the position of this source plus the length of
     * this string.
     */
    private string $peeked = '';

    /**
     * Whether the content of a stream that cannot be rewound has already been
     * taken out of it.
     */
    private bool $isConsumed = false;

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
            $this->consume();

            return $this->takeRest();
        }
    }

    /**
     * @var int<0, max>|null
     */
    public ?int $size {
        /**
         * @throws NotCreatableException When the resource has been closed from the outside
         * @throws NotReadableException When the stream cannot be read
         */
        get {
            if (!$this->isSeekable) {
                return $this->isEof ? $this->position : null;
            }

            $info = @\fstat($this->resource);

            return $info === false ? null : \max(0, $info['size']);
        }
    }

    /**
     * @var int<0, max>
     */
    public int $offset {
        get => $this->position;

        /**
         * @throws InvalidArgumentException When the position is negative
         * @throws LogicException When the stream cannot be moved to an
         *         arbitrary position
         */
        set {
            // Invariant against the callers not covered by static analysis.
            if ($value < 0) {
                throw InvalidArgumentException::becauseOffsetIsNegative($value);
            }

            if (!$this->isSeekable) {
                throw LogicException::becauseStreamIsNotSeekable($this->uri ?? $this->mode);
            }

            // The resource is put where this source is on the next reading of
            // it, so the byte peeked at the old position is given up here.
            $this->peeked = '';
            $this->position = $value;
        }
    }

    public bool $isEof {
        /**
         * @throws NotCreatableException When the resource has been closed from the outside
         * @throws NotReadableException When the stream cannot be read
         */
        get {
            if ($this->peeked !== '') {
                return false;
            }

            return ($this->peeked = $this->fetch(1)) === '';
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

        // The source begins where the resource has been left at, so a resource
        // that has already been read in part is the source of what is left.
        if ($this->isSeekable) {
            $this->position = \max(0, (int) @\ftell($stream));
        }
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
     * @throws InvalidArgumentException When the number of bytes is not positive
     * @throws NotCreatableException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read
     */
    public function read(int $bytes): string
    {
        // Invariant against the callers not covered by static analysis.
        if ($bytes < 1) {
            throw InvalidArgumentException::becauseBytesCountIsNotPositive($bytes);
        }

        $peeked = $this->peeked;

        // The byte that has been peeked at is a part of the result, so only
        // the rest of the requested data is read out of the resource. It is
        // given up after the reading, which is what tells the resource where
        // this source actually is.
        if ($peeked === '') {
            $result = $this->fetch($bytes);
        } elseif ($bytes === 1) {
            $result = $peeked;
        } else {
            $result = $peeked . $this->fetch($bytes - 1);
        }

        $this->peeked = '';
        $this->position += \strlen($result);

        return $result;
    }

    /**
     * Reads everything the resource has left, including the byte that has
     * been peeked at.
     *
     * @throws NotCreatableException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read
     */
    private function takeRest(): string
    {
        $this->synchronize();

        $peeked = $this->peeked;
        $this->peeked = '';

        \error_clear_last();

        $result = @\stream_get_contents($this->resource);

        if ($result === false) {
            throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
        }

        $result = $peeked . $result;

        // A stream that can be rewound is put back where this source is on the
        // next reading of it. The one that cannot is left at its very end.
        if (!$this->isSeekable) {
            $this->position += \strlen($result);
        }

        return $result;
    }

    /**
     * Reads the given number of bytes out of the resource at the position it
     * currently is at.
     *
     * @param int<1, max> $bytes
     * @throws NotCreatableException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read
     */
    private function fetch(int $bytes): string
    {
        $this->synchronize();

        \error_clear_last();

        $result = @\fread($this->resource, $bytes);

        if ($result === false) {
            throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
        }

        return $result;
    }

    /**
     * Hands the resource the position this source is at, which the resource
     * is not necessarily left at: it may have been given away at some other
     * one, and taking the whole content out moves it to the end.
     *
     * @throws NotCreatableException When the resource has been closed from the outside
     */
    private function synchronize(): void
    {
        if (!$this->isSeekable) {
            return;
        }

        $expected = $this->position + \strlen($this->peeked);

        if (\ftell($this->resource) !== $expected) {
            @\fseek($this->resource, $expected);
        }
    }

    /**
     * Tells that the content of the stream is about to be taken out of it,
     * which a stream that cannot be rewound only survives once.
     *
     * @throws NotReadableException When the stream has already been read out
     */
    private function consume(): void
    {
        if ($this->isSeekable) {
            return;
        }

        if ($this->isConsumed) {
            throw NotReadableException::becauseStreamIsAlreadyRead($this->uri ?? $this->mode);
        }

        $this->isConsumed = true;
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
     *     offset: int<0, max>,
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
            'offset' => $this->position,
        ];
    }

    /**
     * Unserializes the stream object
     *
     * @param array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     *     offset: int<0, max>,
     *     ...
     * } $data
     * @throws NotReadableException When the stream cannot be opened
     * @throws LogicException When the stream cannot be moved to the position
     *         the source is at
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

        if ($data['offset'] > 0 && !$this->isSeekable) {
            throw LogicException::becauseStreamIsNotSeekable($data['uri']);
        }

        $this->position = $data['offset'];

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
