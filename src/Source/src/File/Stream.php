<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\File;

/**
 * Class Stream
 */
class Stream extends Readable
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * Stream constructor.
     *
     * @param resource $stream
     */
    public function __construct($stream)
    {
        $this->stream = $stream;
    }

    /**
     * @return bool
     */
    private function isSeekable(): bool
    {
        return (bool)(\stream_get_meta_data($this->stream)['seekable'] ?? false);
    }

    /**
     * @return void
     */
    private function rewind(): void
    {
        if ($this->isSeekable()) {
            \rewind($this->stream);
        }
    }

    /**
     * @return string
     */
    protected function read(): string
    {
        $this->rewind();

        return \stream_get_contents($this->stream);
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $this->rewind();

        return $this->stream;
    }
}
