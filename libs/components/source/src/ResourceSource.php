<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Stream\ReadableStreamInterface;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Stream\ForwardResourceStream;
use Phplrt\Source\Stream\SeekableResourceStream;

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
     * Whether the content of a stream that cannot be rewound has already been
     * taken out of it.
     */
    private bool $isConsumed = false;

    public string $content {
        /**
         * @throws NotCreatableException When the stream has been closed from the outside
         * @throws NotReadableException When the stream cannot be read
         */
        get {
            $this->consume();

            \error_clear_last();

            $result = $this->isSeekable
                ? @\stream_get_contents($this->stream, offset: 0)
                : @\stream_get_contents($this->stream);

            if ($result === false) {
                throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
            }

            return $result;
        }
    }

    /**
     * @var int<0, max>|null
     */
    public ?int $size {
        get {
            // The size of a stream that cannot be rewound is not known in
            // advance: "fstat()" reports zero for a pipe that is full of data.
            if (!$this->isSeekable) {
                return null;
            }

            $info = @\fstat($this->stream);

            return $info === false ? null : \max(0, $info['size']);
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
        public readonly mixed $stream,
        /**
         * Whether the resource stream is closed along with this object.
         */
        public readonly bool $autoclose = false,
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
    }

    /**
     * @throws NotCreatableException When the stream has been closed from the outside
     * @throws NotReadableException When the stream has already been read out
     * @throws NotAccessibleException When the stream cannot be rewound
     */
    public function createStream(): ReadableStreamInterface
    {
        $this->consume();

        // The resource belongs to whoever has passed it in, so a cursor is
        // never the one to close it.
        return $this->isSeekable
            ? new SeekableResourceStream($this->stream)
            : new ForwardResourceStream($this->stream);
    }

    /**
     * Tells that the content of the stream is about to be taken out of it,
     * which a stream that cannot be rewound only survives once.
     *
     * @throws NotCreatableException When the stream has been closed from the outside
     * @throws NotReadableException When the stream has already been read out
     */
    private function consume(): void
    {
        if (!\is_resource($this->stream)) {
            throw NotCreatableException::becauseSourceIs('closed resource');
        }

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
        return \str_contains($mode, 'r') || \str_contains($mode, '+');
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
     * The position the stream has been left at is not a part of the source,
     * so what is remembered is only how to open it over again.
     *
     * @return array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     * }
     * @throws \LogicException When the stream does not have a URI
     */
    public function __serialize(): array
    {
        if ($this->uri === null) {
            throw new \LogicException('Could not serialize stream without URI');
        }

        return [
            'uri' => $this->uri,
            'mode' => $this->mode,
        ];
    }

    /**
     * Unserializes the stream object
     *
     * @param array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     *     ...
     * } $data
     * @throws NotReadableException When the stream cannot be opened
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
