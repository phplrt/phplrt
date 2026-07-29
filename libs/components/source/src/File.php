<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;

/**
 * Implementing a readable object that references a real physical file
 */
class File extends Readable implements FileInterface
{
    /**
     * The modification time and the size of the file at the moment its
     * content has been read.
     */
    private string $memoizedAt = '';

    public mixed $stream {
        /**
         * @throws NotReadableException When the file cannot be opened for reading
         */
        get {
            if (!$this->isReadable) {
                throw NotReadableException::becauseFileNotReadable($this->pathname);
            }

            $stream = \fopen($this->pathname, 'rb');

            if (!\is_resource($stream)) {
                throw NotReadableException::becauseFileNotReadable($this->pathname);
            }

            return $stream;
        }
    }

    public private(set) string $content {
        /**
         * @throws NotFoundException When the file does not exist
         * @throws NotReadableException When the file cannot be read
         */
        get {
            // PHP remembers what it has learned about a file, so everything it
            // knows about this one is forgotten before it is asked about again:
            // the file may have been changed by somebody else meanwhile.
            \clearstatcache(true, $this->pathname);

            if (!\is_file($this->pathname)) {
                throw NotFoundException::becauseFileNotFound($this->pathname);
            }

            // The modification time alone is measured in seconds, so the size
            // is taken into account as well: a file rewritten within the very
            // same second is unlikely to keep its length...
            //
            // However, this is theoretically possible, but not critical
            // for our purposes =)
            $state = $this->modifiedAt . ':' . $this->size;

            // A file that has not been changed since it was read contains the
            // very same thing, so it is not read over again
            if ($this->memoizedAt === $state && isset($this->content)) {
                return $this->content;
            }

            \error_clear_last();

            $result = @\file_get_contents($this->pathname);

            if ($result === false) {
                throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
            }

            $this->memoizedAt = $state;

            return $this->content = $result;
        }
    }

    /**
     * Gets a file size
     *
     * @var int<0, max>
     */
    public int $size {
        get => (int) \filesize($this->pathname);
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
    ) {}
}
