<?php

declare(strict_types=1);

namespace Phplrt\Source\ContentReader;

use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\MemoizableInterface;

class FileContentReader implements ContentReaderInterface, MemoizableInterface
{
    /**
     * @var string
     */
    private const ERROR_NOT_READABLE = 'An error occurred while trying to read a file "%s"';

    private string $pathname;

    private ?string $content = null;

    public function __construct(string $pathname)
    {
        $this->pathname = $pathname;
    }

    public function getContents(): string
    {
        if ($this->content === null) {
            $this->content = $this->read();
        }

        return $this->content;
    }

    private function read(): string
    {
        $result = @\file_get_contents($this->pathname);

        if (!\is_string($result)) {
            throw new NotReadableException(\sprintf(self::ERROR_NOT_READABLE, $this->pathname));
        }

        return $result;
    }

    public function refresh(): void
    {
        $this->content = null;
    }
}
