<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\Exception\HashCalculationException;
use Phplrt\Source\Exception\NotAccessibleException;

class Source extends Readable implements PreferContentReadingInterface
{
    /**
     * Content hash value.
     *
     * @var non-empty-string|null
     */
    private ?string $hash = null;

    /**
     * @var resource|null
     */
    private mixed $stream = null;

    /**
     * @psalm-taint-sink file $temp
     */
    public function __construct(
        private readonly string $content,
        /**
         * Hashing algorithm for the source.
         *
         * @var non-empty-string
         */
        private readonly string $algo = SourceFactory::DEFAULT_HASH_ALGO,
        /**
         * The name of the temporary stream, which is used as a resource during
         * the reading of the source.
         *
         * @var non-empty-string
         */
        private readonly string $temp = SourceFactory::DEFAULT_TEMP_STREAM
    ) {
        assert($algo !== '', 'Hashing algorithm name must not be empty');
        assert($temp !== '', 'Temporary stream name must not be empty');
    }

    public function getContents(): string
    {
        return $this->content;
    }

    /**
     * @throws NotAccessibleException
     */
    public function getStream(): mixed
    {
        if (!\is_resource($this->stream)) {
            $this->stream = \fopen($this->temp, 'rb+');

            if (@\fwrite($this->stream, $this->content) === false) {
                throw NotAccessibleException::fromStreamWriteOperation($this->temp);
            }
        }

        if (@\rewind($this->stream) === false) {
            throw NotAccessibleException::fromStreamRewindOperation($this->temp);
        }

        return $this->stream;
    }

    /**
     * @throws HashCalculationException
     */
    public function getHash(): string
    {
        try {
            return $this->hash ??= \hash($this->algo, $this->content);
        } catch (\ValueError $e) {
            throw HashCalculationException::fromInvalidHashAlgo($this->algo, $e);
        }
    }
}
