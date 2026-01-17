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
     * @var non-empty-string
     */
    public readonly string $mode;

    /**
     * Returns {@see true} in case of stream is local
     */
    public readonly bool $isLocal;

    /**
     * @var int<0, max>
     */
    public int $offset {
        /** @phpstan-ignore-next-line : False-positive, offset cannot be negative */
        get => (int) \ftell($this->stream);
    }

    public function __construct(
        /**
         * @var resource
         */
        public readonly mixed $stream,
        HasherInterface $hasher,
    ) {
        parent::__construct($hasher);

        $metadata = \stream_get_meta_data($stream);

        $this->uri = $this->getUriFromMetadata($metadata);
        $this->mode = $this->getModeFromMetadata($metadata);
        $this->isLocal = $this->getIsLocalInfoFromMetadata($metadata);
    }

    /**
     * @param StreamMetaType $metadata
     */
    private function getIsLocalInfoFromMetadata(array $metadata): bool
    {
        return isset($metadata['uri'])
            && \stream_is_local($metadata['uri']);
    }

    /**
     * @param StreamMetaType $metadata
     * @return non-empty-string
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
     * @param StreamMetaType $metadata
     * @return non-empty-string|null
     */
    private function getUriFromMetadata(array $metadata): ?string
    {
        $uri = $metadata['uri'] ?? null;

        if ($uri === null || $uri === '') {
            return null;
        }

        return $uri;
    }

    /**
     * @return array<non-empty-string, mixed>
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
     * @param array{
     *     uri: non-empty-string,
     *     mode: non-empty-string,
     *     seek: int<0, max>,
     *     hasher: HasherInterface,
     *     ...
     * } $data
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
