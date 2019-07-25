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
 * @internal A ReadableInterface internal implementation
 */
class Stream extends Readable
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var string|null
     */
    private $content;

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
     * @return string
     */
    public function getContents(): string
    {
        if ($this->content) {
            if (\stream_get_meta_data($this->stream)['seekable'] ?? false === true) {
                \rewind($this->stream);
            }

            $this->content = \stream_get_contents($this->stream);
        }

        return $this->content;
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->content = null;

        parent::refresh();
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return string
     */
    protected function calculateHash(): string
    {
        return \hash(static::HASH_ALGORITHM, (string)$this->stream);
    }
}
