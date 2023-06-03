<?php

declare(strict_types=1);

namespace Phplrt\Source\ContentReader;

interface ContentReaderInterface
{
    /**
     * @return string
     */
    public function getContents(): string;
}
