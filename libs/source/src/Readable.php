<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\ContentReader\ContentReaderInterface;
use Phplrt\Source\StreamReader\StreamReaderInterface;

class Readable implements ReadableInterface, MemoizableInterface
{
    use FactoryTrait;

    /**
     * @var string
     */
    protected const HASH_ALGORITHM = 'crc32';

    private StreamReaderInterface $stream;

    private ContentReaderInterface $content;

    public function __construct(StreamReaderInterface $stream, ContentReaderInterface $content)
    {
        $this->stream = $stream;
        $this->content = $content;
    }

    public function refresh(): void
    {
        if ($this->stream instanceof MemoizableInterface) {
            $this->stream->refresh();
        }

        if ($this->content instanceof MemoizableInterface) {
            $this->content->refresh();
        }
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream->getStream();
    }

    public function getContents(): string
    {
        return $this->content->getContents();
    }

    public function getHash(): string
    {
        return \hash(static::HASH_ALGORITHM, $this->getContents());
    }
}
