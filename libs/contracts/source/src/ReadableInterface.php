<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

interface ReadableInterface extends ReadableStreamInterface
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
}
