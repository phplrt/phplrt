<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

interface ReadableInterface
{
    /**
     * Gets the resource stream of the source
     *
     * @throws SourceExceptionInterface may occur during the inability to
     *         open or some operations with the resource stream
     */
    public mixed $stream {
        get;
    }

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
     * Gets the identifier (hash) string of the source object.
     *
     * If the value of the source content changes, the hash value will
     * also be changed.
     *
     * @var non-empty-string
     *
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         convert object state information into a hash
     */
    public string $hash {
        get;
    }
}
