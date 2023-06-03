<?php

declare(strict_types=1);

namespace Phplrt\Source\ContentReader;

class ContentReader implements ContentReaderInterface
{
    /**
     * @var string
     */
    private string $content;

    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return $this->content;
    }
}
