<?php

declare(strict_types=1);

namespace Phplrt\Source\ContentReader;

use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\StreamUtil;

class StreamContentReader implements ContentReaderInterface
{
    /**
     * @var string
     */
    private const METADATA_KEY_SEEKABLE = 'seekable';

    /**
     * @var string
     */
    private const ERROR_NOT_SEEKABLE =
        'Impossible to read a stream from the beginning for non-seekable stream';

    /**
     * @var resource
     */
    private $stream;

    /**
     * @param resource $stream
     */
    public function __construct($stream)
    {
        \assert(StreamUtil::isNonClosedStream($stream));

        $this->stream = $stream;
    }

    public function getContents(): string
    {
        $this->rewind();

        return \stream_get_contents($this->stream);
    }

    private function rewind(): void
    {
        // In the case that the cursor is not at the beginning.
        if (\ftell($this->stream) !== 0) {
            // If at the same time the stream is not a seekable,
            // then we cannot reset its cursor.
            if (!$this->isSeekable()) {
                throw new NotAccessibleException(self::ERROR_NOT_SEEKABLE);
            }

            \rewind($this->stream);
        }
    }

    private function isSeekable(): bool
    {
        return (bool)(\stream_get_meta_data($this->stream)[self::METADATA_KEY_SEEKABLE] ?? false);
    }

    public function __serialize(): array
    {
        return StreamUtil::serialize($this->stream);
    }

    public function __unserialize(array $data): void
    {
        $this->stream = StreamUtil::unserialize($data);
    }
}
