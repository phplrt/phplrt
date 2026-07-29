<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

interface FileInterface extends ReadableInterface
{
    /**
     * Gets the physical path to the source file.
     *
     * @var non-empty-string
     */
    public string $pathname {
        get;
    }
}
