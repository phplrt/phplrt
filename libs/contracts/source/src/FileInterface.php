<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

interface FileInterface extends ReadableInterface
{
    /**
     * Returns the physical path to the source file.
     *
     * @return non-empty-string
     */
    public function getPathname(): string;
}
