<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;

/**
 * Implementing a readable object that references a real physical file
 *
 * @final please do not inherit from this class
 */
class FileSource extends Readable implements FileInterface
{
    /**
     * The file this source owns, opened at the first reading of it.
     */
    private ResourceSource $reader {
        /**
         * @throws NotFoundException When the file does not exist
         * @throws NotReadableException When the file cannot be opened for reading
         */
        get => $this->reader ??= $this->open();
    }

    /**
     * @var int<0, max>
     */
    public int $offset {
        /**
         * @throws NotReadableException When the file cannot be opened for reading
         */
        get => $this->reader->offset;
    }

    public bool $isEof {
        /**
         * @throws NotReadableException When the file cannot be opened or read
         */
        get => $this->reader->isEof;
    }

    public private(set) string $content {
        /**
         * @throws NotFoundException When the file does not exist
         * @throws NotReadableException When the file cannot be opened or read
         */
        // The file is read over again rather than through the cursor of this
        // source, which is somewhere in the middle of it by then.
        get => $this->content ??= $this->open()->content;
    }

    /**
     * Gets a file size
     *
     * @var int<0, max>
     */
    public int $size {
        get => $this->isReadable ? (int) @\filesize($this->pathname) : 0;
    }

    /**
     * Gets a file modification time
     *
     * @var int<0, max>
     */
    public int $modifiedAt {
        get => $this->isReadable ? (int) \filemtime($this->pathname) : 0;
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

    /**
     * @api
     *
     * @param non-empty-string $pathname
     */
    public static function createFromPathname(string $pathname): self
    {
        return new self($pathname);
    }

    /**
     * @api
     *
     * @throws NotCreatableException When the reference carries no pathname
     */
    public static function createFromSplFileInfo(\SplFileInfo $info): self
    {
        $pathname = $info->getPathname();

        if ($pathname === '') {
            throw NotCreatableException::becauseSourceIs('empty pathname');
        }

        return self::createFromPathname($pathname);
    }

    /**
     * @throws NotReadableException When the file cannot be opened or read
     */
    public function read(int $bytes): string
    {
        return $this->reader->read($bytes);
    }

    /**
     * Takes the file over: from here on it belongs to this source, which
     * holds it against being written to until it is given up again.
     *
     * @throws NotFoundException When the file does not exist
     * @throws NotReadableException When the file cannot be opened for reading
     */
    private function open(): ResourceSource
    {
        \clearstatcache(true, $this->pathname);

        if (!$this->isExists) {
            throw NotFoundException::becauseFileNotFound($this->pathname);
        }

        if (!$this->isReadable) {
            throw NotReadableException::becauseFileNotReadable($this->pathname);
        }

        $stream = @\fopen($this->pathname, 'rb');

        if (!\is_resource($stream)) {
            throw NotReadableException::becauseFileNotReadable($this->pathname);
        }

        // Closing the handle is what gives the file up again, so the source
        // that owns the handle owns the lock along with it.
        @\flock($stream, \LOCK_SH);

        return new ResourceSource($stream, autoclose: true);
    }
}
