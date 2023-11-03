<?php

declare(strict_types=1);

namespace Phplrt\Source\ContentReader;

class ContentReader implements ContentReaderInterface
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getContents(): string
    {
        return $this->content;
    }
}
