<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\Exception\ClosedStreamException;
use Phplrt\Source\Exception\NegativeOffsetException;
use Phplrt\Source\Exception\NonPositiveBytesCountException;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Exception\OffsetOutOfRangeException;
use Phplrt\Source\Exception\StreamNotOpenedException;
use Phplrt\Source\Exception\StreamNotReadableException;
use Phplrt\Source\Exception\StreamNotRewindableException;
use Phplrt\Source\Exception\StreamNotSeekableException;
use Phplrt\Source\Exception\StreamNotSerializableException;
use Phplrt\Source\Exception\StreamReadingException;

/**
 * Implementing a readable object that references to a resource stream
 *
 * The source begins where the stream has been left at the moment it has been
 * given away, so a stream that has already been read in part is the source of
 * what is left in it.
 *
 * A stream that cannot be rewound is read forwards only: everything it has
 * given away is gone, so a fragment located before the position it is at is no
 * longer available. Reading such a source in an arbitrary order is a matter of
 * taking its data over first ({@see toSeekableSource()}).
 *
 * Note: Starting with PHP 8.4, in the future, all property annotations
 *       described below will be expressed as full properties.
 *
 * @property-read string $content The whole content of the source.
 *
 *         Reading it throws a {@see ClosedStreamException} when the stream has
 *         been closed from the outside and a {@see NotReadableException} when
 *         the stream cannot be read, or cannot be rewound and has already been
 *         read past the position the source begins at.
 *
 * @final please do not inherit from this class
 */
class ResourceSource extends Readable
{
    /**
     * The number of bytes taken out of a stream at once while it is being
     * read through.
     *
     * @var int<1, max>
     */
    private const CHUNK_SIZE = 65536;

    /**
     * The position of the stream this source begins at.
     *
     * @var int<0, max>
     */
    private readonly int $initial;

    /**
     * The resource this source reads from.
     *
     * @var resource
     */
    private mixed $stream;

    /**
     * The data of the source, in case it has already been read.
     */
    private ?string $data = null;

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
     * @param resource $stream
     * @throws NotCreatableException When the given value is not a resource stream
     * @throws StreamNotReadableException When the resource stream is not open
     *         for reading
     */
    public function __construct(
        mixed $stream,
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

        $this->stream = $stream;

        $metadata = \stream_get_meta_data($stream);
        $uri = $metadata['uri'] ?? '';

        $this->uri = $uri === '' ? null : $uri;
        $this->mode = $metadata['mode'] === '' ? 'rb' : $metadata['mode'];
        $this->isLocal = $this->uri !== null && \stream_is_local($this->uri);
        $this->isSeekable = $metadata['seekable'];

        if (!\str_contains($this->mode, 'r') && !\str_contains($this->mode, '+')) {
            throw StreamNotReadableException::becauseStreamIsNotReadable($this->uri ?? $this->mode);
        }

        $this->initial = \max(0, (int) @\ftell($stream));
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
     * @throws NegativeOffsetException When the offset is negative
     * @throws NonPositiveBytesCountException When the number of bytes is not positive
     * @throws OffsetOutOfRangeException When the offset points beyond what an
     *         integer holds
     * @throws ClosedStreamException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read or cannot be
     *         rewound to the given offset
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

        if ($offset > \PHP_INT_MAX - $this->initial) {
            throw OffsetOutOfRangeException::becauseOffsetIsOutOfRange($offset, $this->initial);
        }

        $this->seek($this->initial + $offset);

        return $this->fetch($bytes);
    }

    /**
     * Takes the data of the stream over and gives it away as a source that is
     * read in an arbitrary order, which is what a stream that cannot be
     * rewound does not allow.
     *
     * The whole of the stream is read into the memory, so a source large
     * enough exhausts it. A stream that cannot be rewound is left with nothing
     * in it, so this is the last thing it is read by.
     *
     * @api
     *
     * @return StringSource|VirtualSource a virtual file in case the stream has
     *         a URI, or a plain string source otherwise
     * @throws ClosedStreamException When the stream has been closed from the outside
     * @throws NotReadableException When the stream cannot be read, or cannot be
     *         rewound and has already been read past the position the source
     *         begins at
     */
    public function toSeekableSource(): StringSource|VirtualSource
    {
        $content = $this->getContents();

        if ($this->uri === null) {
            return new StringSource($content);
        }

        return new VirtualSource($this->uri, new StringSource($content));
    }

    /**
     * @throws ClosedStreamException When the stream has been closed from the outside
     * @throws NotReadableException When the stream cannot be read, or cannot be
     *         rewound and has already been read past the position the source
     *         begins at
     */
    public function __get(string $property): mixed
    {
        switch ($property) {
            case 'content':
                return $this->getContents();

            default:
                throw new \Error(\sprintf('Undefined property %s::$%s', static::class, $property));
        }
    }

    /**
     * An alias of the {@see $content} property.
     *
     * @throws ClosedStreamException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read, or cannot be
     *         rewound to the position the source begins at
     */
    public function getContents(): string
    {
        return $this->data ??= $this->readAll();
    }

    /**
     * Reads the whole data of the source.
     *
     * @throws ClosedStreamException When the resource has been closed from the outside
     * @throws NotReadableException When the stream cannot be read, or cannot be
     *         rewound to the position the source begins at
     */
    private function readAll(): string
    {
        $this->seek($this->initial);

        \error_clear_last();

        $result = @\stream_get_contents($this->requireStream());

        if ($result === false) {
            throw StreamReadingException::becauseStreamCannotBeRead(\error_get_last());
        }

        return $result;
    }

    /**
     * Reads the given number of bytes out of the stream at the position it
     * currently is at.
     *
     * @param int<1, max> $bytes
     * @throws ClosedStreamException When the resource has been closed from the outside
     * @throws StreamReadingException When the stream cannot be read
     */
    private function fetch(int $bytes): string
    {
        \error_clear_last();

        $result = @\fread($this->requireStream(), $bytes);

        if ($result === false) {
            throw StreamReadingException::becauseStreamCannotBeRead(\error_get_last());
        }

        return $result;
    }

    /**
     * Moves the stream to the given position of the stream itself, which it is
     * not necessarily left at: reading through it moves it elsewhere.
     *
     * @param int<0, max> $offset
     * @throws ClosedStreamException When the resource has been closed from the outside
     * @throws StreamNotRewindableException When the stream cannot be rewound to
     *         the given position
     * @throws StreamReadingException When the stream cannot be read through to
     *         the given position
     */
    private function seek(int $offset): void
    {
        $stream = $this->requireStream();

        $current = \max(0, (int) @\ftell($stream));

        if ($current === $offset) {
            return;
        }

        if ($this->isSeekable) {
            @\fseek($stream, $offset);

            return;
        }

        // Note: Whatever a stream that cannot be rewound has given away is
        //       gone, so it only ever moves forwards, which is done by
        //       reading through it.
        if ($offset < $current) {
            throw StreamNotRewindableException::becauseStreamCannotBeRewound($this->uri ?? $this->mode);
        }

        for ($rest = $offset - $current; $rest >= 1; $rest -= \strlen($chunk)) {
            $chunk = $this->fetch(\min(self::CHUNK_SIZE, $rest));

            // Note: Nothing is there right now, so there is nothing to move
            //       over either, and the reading that follows says as much.
            if ($chunk === '') {
                return;
            }
        }
    }

    /**
     * Gives the resource back, in case it has not been closed from the outside.
     *
     * @return resource
     * @throws ClosedStreamException When the resource has been closed from the outside
     */
    private function requireStream(): mixed
    {
        if (!\is_resource($this->stream)) {
            throw ClosedStreamException::becauseStreamIsClosed();
        }

        return $this->stream;
    }

    /**
     * Serializes the stream object
     *
     * @return array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     *     initial: int<0, max>,
     * }
     * @throws StreamNotSerializableException When the stream does not have a URI
     */
    public function __serialize(): array
    {
        if ($this->uri === null) {
            throw StreamNotSerializableException::becauseStreamHasNoUri($this->mode);
        }

        return [
            'uri' => $this->uri,
            'mode' => $this->mode,
            'initial' => $this->initial,
        ];
    }

    /**
     * Unserializes the stream object
     *
     * @param array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     *     initial: int<0, max>,
     *     ...<string, mixed>
     * } $data
     * @throws StreamNotOpenedException When the stream cannot be opened
     * @throws StreamNotSeekableException When the stream cannot be moved to the
     *         position the source begins at
     */
    public function __unserialize(array $data): void
    {
        \error_clear_last();

        $stream = @\fopen($data['uri'], $data['mode']);

        if ($stream === false) {
            throw StreamNotOpenedException::becauseStreamCannotBeOpened($data['uri'], \error_get_last());
        }

        $this->stream = $stream;
        $this->uri = $data['uri'];
        $this->mode = $data['mode'];
        $this->isLocal = \stream_is_local($data['uri']);
        $this->isSeekable = \stream_get_meta_data($stream)['seekable'];

        if ($data['initial'] > 0 && !$this->isSeekable) {
            throw StreamNotSeekableException::becauseStreamIsNotSeekable($data['uri']);
        }

        $this->initial = $data['initial'];

        // The stream has been opened here rather than passed in, so this
        // object is the one to close it.
        $this->autoclose = true;
    }

    public function __destruct()
    {
        if (!$this->autoclose) {
            return;
        }

        try {
            \fclose($this->requireStream());
        } catch (ClosedStreamException) {
            // Note: The stream has been closed from the outside, so there is
            //       nothing left for this object to give up.
        }
    }
}
