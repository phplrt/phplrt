<?php

declare(strict_types=1);

namespace Phplrt\Source\StreamReader;

interface StreamReaderInterface
{
    /**
     * @return resource
     */
    public function getStream();
}
