<?php

declare(strict_types=1);

namespace Phplrt\Source;

class VirtualFile extends Source implements VirtualFileInterface
{
    /**
     * @psalm-taint-sink file $filename
     * @psalm-taint-sink file $temp
     * @param non-empty-string $algo
     * @param non-empty-string $temp
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        private readonly string $filename,
        string $content,
        string $algo = SourceFactory::DEFAULT_HASH_ALGO,
        string $temp = SourceFactory::DEFAULT_TEMP_STREAM
    ) {
        assert($filename !== '', 'Filename must not be empty');

        parent::__construct($content, $algo, $temp);
    }

    public function getPathname(): string
    {
        return $this->filename;
    }
}
