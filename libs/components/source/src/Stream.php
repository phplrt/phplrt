<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Hash\HasherInterface;

/**
 * Implementing a readable object that references to a resource stream
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
 */
class Stream extends Readable
{
    public string $content {
        /**
         * @throws NotReadableException When the stream cannot be read
         */
        get {
            \error_clear_last();

            $result = @\stream_get_contents($this->stream);

            if ($result === false) {
                throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
            }

            return $result;
        }
    }

    public string $hash {
        get {
            // In the case that the stream is a link to a local file, we can
            // speed up hash generation using the low-level hashing API.
            if ($this->isLocal && $this->uri !== null) {
                return $this->hash ??= $this->hasher->file($this->uri);
            }

            return $this->hash ??= $this->hasher->content($this->content);
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
     * Gets the current offset position in the stream
     *
     * @var int<0, max>
     */
    public int $offset {
        /** @phpstan-ignore-next-line : False-positive, offset cannot be negative */
        get => (int) \ftell($this->stream);
    }

    /**
     * @param resource $stream The resource stream
     */
    public function __construct(
        public readonly mixed $stream,
        HasherInterface $hasher,
    ) {
        parent::__construct($hasher);

        $metadata = \stream_get_meta_data($stream);

        $this->uri = $this->findUriFromMetadata($metadata);
        $this->mode = $this->getModeFromMetadata($metadata);
        $this->isLocal = $this->getIsLocalInfoFromMetadata($metadata);
    }

    /**
     * Extracts "local" bool flag stream information from metadata
     *
     * @param StreamMetaType $metadata Stream metadata array
     *
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
     *
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
     *
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
     *     uri: ?non-empty-string,
     *     mode: non-empty-string,
     *     seek: int<0, max>,
     *     hasher: HasherInterface,
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
            'seek' => $this->offset,
            'hasher' => $this->hasher,
        ];
    }

    /**
     * Unserializes the stream object
     *
     * @param array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     *     seek: int<0, max>,
     *     hasher: HasherInterface,
     *     ...
     * } $data
     *
     * @throws NotReadableException When the stream cannot be opened
     * @throws NotAccessibleException When the stream is not seekable
     */
    public function __unserialize(array $data): void
    {
        \error_clear_last();

        $this->hasher = $data['hasher'];
        $this->isLocal = \stream_is_local($data['uri']);
        $this->mode = $data['mode'];
        $this->uri = $data['uri'];

        $stream = @\fopen($this->uri, $this->mode);

        if ($stream === false) {
            throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
        }

        if (\fseek($stream, $data['seek']) === -1) {
            throw NotAccessibleException::becauseStreamIsNotSeekable($this->uri);
        }

        $this->stream = $stream;
    }
}
