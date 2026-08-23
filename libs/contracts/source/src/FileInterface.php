<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

/**
 * A source code that is stored in a physical file.
 */
interface FileInterface extends ReadableInterface
{
    /**
     * The physical pathname of the file the source is stored in.
     *
     * @var non-empty-string
     */
    public string $pathname {
        get;
    }
}
