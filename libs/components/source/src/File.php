<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Hash\HasherInterface;

/**
 * Implementing a readable object that references a real physical file
 */
class File extends Readable implements FileInterface
{
    public mixed $stream {
        /**
         * @throws NotReadableException When the file cannot be opened for reading
         */
        get {
            $stream = \fopen($this->pathname, 'rb');

            if (!\is_resource($stream)) {
                throw NotReadableException::becauseFileNotReadable($this->pathname);
            }

            return $stream;
        }
    }

    public string $content {
        /**
         * @throws NotFoundException When the file does not exist
         * @throws NotReadableException When the file cannot be read
         */
        get {
            if (!\is_file($this->pathname)) {
                throw NotFoundException::becauseFileNotFound($this->pathname);
            }

            \error_clear_last();

            $result = @\file_get_contents($this->pathname);

            if ($result === false) {
                throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
            }

            return $result;
        }
    }

    public string $hash {
        get {
            return $this->hasher->file($this->pathname);
        }
    }

    /**
     * Gets a file modification time
     *
     * @var int<0, max>
     */
    public int $modifiedAt {
        get => (int) \filemtime($this->pathname);
    }

    /**
     * Returns {@see true} in case of a file exists
     */
    public bool $isExists {
        get => \is_file($this->pathname);
    }

    /**
     * Returns {@see true} in case of a file is readable
     */
    public bool $isReadable {
        get => \is_readable($this->pathname);
    }

    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $pathname,
        HasherInterface $hasher,
    ) {
        parent::__construct($hasher);
    }
}
