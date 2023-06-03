<?php

declare(strict_types=1);

namespace Phplrt\Source\StreamReader;

use Phplrt\Source\StreamUtil;

class StreamReader implements StreamReaderInterface
{
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

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return StreamUtil::serialize($this->stream);
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->stream = StreamUtil::unserialize($data);
    }
}
