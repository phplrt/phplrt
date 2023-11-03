<?php

declare(strict_types=1);

namespace Phplrt\Source\ContentReader;

interface ContentReaderInterface
{
    public function getContents(): string;
}
