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

        /**
         * @throws NotReadableException When the file cannot be opened for reading
         */
        set {
            $this->reader->offset = $value;
        }
    }

    public bool $isSeekable {
        /**
         * @throws NotReadableException When the file cannot be opened for reading
         */
        get => $this->reader->isSeekable;
    }

    public bool $isEof {
        /**
         * @throws NotReadableException When the file cannot be opened or read
         */
        get => $this->reader->isEof;
    }

    public string $content {
        /**
         * @throws NotFoundException When the file does not exist
         * @throws NotReadableException When the file cannot be opened or read
         */
        get => $this->reader->content;
    }

    /**
     * Gets a file size
     *
     * @var int<0, max>
     */
    public int $size {
        /**
         * @throws NotFoundException When the file does not exist
         * @throws NotReadableException When the file cannot be read
         */
        get {
            $size = @\filesize($this->pathname);

            if ($size === false) {
                throw $this->createAccessFailure();
            }

            return \max(0, $size);
        }
    }

    /**
     * Gets a file modification time
     *
     * @var int<0, max>
     */
    public int $modifiedAt {
        /**
         * @throws NotFoundException When the file does not exist
         * @throws NotReadableException When the file cannot be read
         */
        get {
            $time = @\filemtime($this->pathname);

            if ($time === false) {
                throw $this->createAccessFailure();
            }

            return \max(0, $time);
        }
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
        $stream = @\fopen($this->pathname, 'rb');

        if (!\is_resource($stream)) {
            throw $this->createAccessFailure();
        }

        // Closing the handle is what gives the file up again, so the source
        // that owns the handle owns the lock along with it.
        @\flock($stream, \LOCK_SH);

        return new ResourceSource($stream, autoclose: true);
    }

    /**
     * Tells what the file that could not be accessed is about, which costs a
     * request to the file system and is therefore found out only after the
     * access has failed.
     */
    private function createAccessFailure(): NotReadableException
    {
        if (!$this->isExists) {
            return NotFoundException::becauseFileNotFound($this->pathname);
        }

        return NotReadableException::becauseFileNotReadable($this->pathname);
    }
}
