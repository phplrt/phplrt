<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\StreamReader;

use Phplrt\Source\Internal\Util;

class StreamReader implements StreamReaderInterface
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * StreamReader constructor.
     *
     * @param resource $stream
     */
    public function __construct($stream)
    {
        assert(Util::isNonClosedStream($stream));

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
        return Util::serialize($this->stream);
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->stream = Util::unserialize($data);
    }
}
