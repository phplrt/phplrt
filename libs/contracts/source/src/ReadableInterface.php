<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\Stream\ReadableStreamInterface;

interface ReadableInterface
{
    /**
     * Gets the source content as string
     *
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data and/or convert it to a string
     */
    public string $content {
        get;
    }

    /**
     * Gets the size of the source in bytes or {@see null} in case it cannot
     * be known without reading the source out.
     *
     * @var int<0, max>|null
     *
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data
     */
    public ?int $size {
        get;
    }

    /**
     * Creates a cursor over the source's bytes, placed at its beginning.
     *
     * Each call returns a cursor of its own, so reading through one of them
     * does not affect the others. A source that cannot be rewound is the only
     * exception: it is readable once, and the cursors of such a source share
     * the position with each other.
     *
     * @throws SourceExceptionInterface may occur during the inability to open
     *         or some operations with the source
     */
    public function createStream(): ReadableStreamInterface;
}
