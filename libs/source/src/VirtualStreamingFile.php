<?php

declare(strict_types=1);

namespace Phplrt\Source;

class VirtualStreamingFile extends Stream implements VirtualFileInterface
{
    /**
     * @var non-empty-string
     * @psalm-readonly-allow-private-mutation
     */
    private string $filename;

    /**
     * @psalm-taint-sink file $filename
     *
     * @param non-empty-string $filename
     * @param resource $stream
     * @param non-empty-string $algo
     * @param int<1, max> $chunkSize
     */
    public function __construct(
        string $filename,
        $stream,
        string $algo = SourceFactory::DEFAULT_HASH_ALGO,
        int $chunkSize = SourceFactory::DEFAULT_CHUNK_SIZE
    ) {
        assert($filename !== '', 'Filename must not be empty');

        $this->filename = $filename;

        parent::__construct($stream, $algo, $chunkSize);
    }

    public function getPathname(): string
    {
        return $this->filename;
    }

    /**
     * @return array{}|array{
     *  file: non-empty-string,
     *  uri: non-empty-string,
     *  mode: non-empty-string,
     *  seek: int<0, max>,
     *  algo: non-empty-string,
     *  chunk: int<1, max>
     * }
     */
    public function __serialize(): array
    {
        return \array_merge(parent::__serialize(), [
            'file' => $this->filename,
        ]);
    }

    /**
     * @param array{
     *  file?: non-empty-string,
     *  uri?: non-empty-string,
     *  mode?: non-empty-string,
     *  seek?: int,
     *  algo?: string,
     *  chunk?: int
     * } $data
     *
     * @throws \ErrorException
     */
    public function __unserialize(array $data): void
    {
        $this->filename = $data['file'] ?? 'php://memory';

        parent::__unserialize($data);
    }
}
