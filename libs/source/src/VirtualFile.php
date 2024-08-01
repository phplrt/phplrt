<?php

declare(strict_types=1);

namespace Phplrt\Source;

class VirtualFile extends Source implements VirtualFileInterface
{
    /**
     * @var non-empty-string
     *
     * @psalm-readonly-allow-private-mutation
     */
    private string $filename;

    /**
     * @psalm-taint-sink file $filename
     * @psalm-taint-sink file $temp
     * @param non-empty-string $filename
     * @param non-empty-string $algo
     * @param non-empty-string $temp
     */
    public function __construct(
        string $filename,
        string $content,
        string $algo = SourceFactory::DEFAULT_HASH_ALGO,
        string $temp = SourceFactory::DEFAULT_TEMP_STREAM
    ) {
        assert($filename !== '', 'Filename must not be empty');

        $this->filename = $filename;

        parent::__construct($content, $algo, $temp);
    }

    public function getPathname(): string
    {
        return $this->filename;
    }
}
