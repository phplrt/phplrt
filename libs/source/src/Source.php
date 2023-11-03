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
    private $stream = null;

    /**
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    private string $content;

    /**
     * @var non-empty-string
     * @psalm-readonly-allow-private-mutation
     */
    private string $algo = SourceFactory::DEFAULT_HASH_ALGO;

    /**
     * @var non-empty-string
     * @psalm-readonly-allow-private-mutation
     */
    private string $temp = SourceFactory::DEFAULT_TEMP_STREAM;

    /**
     * @psalm-taint-sink file $temp
     *
     * @param non-empty-string $algo Hashing algorithm for the source.
     * @param non-empty-string $temp The name of the temporary stream, which is
     *        used as a resource during the reading of the source.
     */
    public function __construct(
        string $content,
        string $algo = SourceFactory::DEFAULT_HASH_ALGO,
        string $temp = SourceFactory::DEFAULT_TEMP_STREAM
    ) {
        assert($algo !== '', 'Hashing algorithm name must not be empty');
        assert($temp !== '', 'Temporary stream name must not be empty');

        $this->temp = $temp;
        $this->algo = $algo;
        $this->content = $content;
    }

    public function getContents(): string
    {
        return $this->content;
    }

    /**
     * @throws NotAccessibleException
     */
    public function getStream()
    {
        if (!\is_resource($this->stream)) {
            /** @var resource $memory */
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
