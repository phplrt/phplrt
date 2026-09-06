<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Exception\FileNotFoundException;
use Phplrt\Source\Exception\FileNotReadableException;
use Phplrt\Source\Exception\InvalidArgumentException;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotReadableException;

/**
 * Implementing a readable object that references a real physical file
 *
 * Note: Starting with PHP 8.4, in the future, all property annotations
 *       described below will be expressed as full properties.
 *
 * @property-read string $content The whole content of the file.
 *
 *         Reading it throws a {@see FileNotFoundException} when the file does
 *         not exist and a {@see NotReadableException} when the file cannot be
 *         opened or read.
 * @property-read int<0, max> $size The size of the file, in bytes.
 *
 *         Reading it throws a {@see FileNotFoundException} when the file does
 *         not exist and a {@see FileNotReadableException} when the file cannot
 *         be read.
 * @property-read int<0, max> $modifiedAt The time the file has been modified at.
 *
 *         Reading it throws a {@see FileNotFoundException} when the file does
 *         not exist and a {@see FileNotReadableException} when the file cannot
 *         be read.
 * @property-read bool $isExists Contains {@see true} in case of a file exists.
 * @property-read bool $isReadable Contains {@see true} in case of a file is
 *         readable.
 *
 * @final please do not inherit from this class
 */
class FileSource extends Readable implements FileInterface
{
    /**
     * The file this source owns, opened at the first reading of it.
     */
    private ?ResourceSource $reader = null;

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
     * @throws InvalidArgumentException When the offset is negative or points
     *         beyond what an integer holds, or the number of bytes is not
     *         positive
     * @throws FileNotFoundException When the file does not exist
     * @throws NotReadableException When the file cannot be opened or read
     */
    public function read(int $offset, int $bytes): string
    {
        $target = $this->getTargetSource();

        return $target->read($offset, $bytes);
    }

    /**
     * @throws FileNotFoundException When the file does not exist
     * @throws NotReadableException When the file cannot be opened or read
     */
    public function __get(string $property): mixed
    {
        switch ($property) {
            case 'content':
                return $this->getContents();

            case 'size':
                return $this->getSize();

            case 'modifiedAt':
                return $this->getModificationTime();

            case 'isExists':
                return $this->isExists();

            case 'isReadable':
                return $this->isReadable();

            default:
                throw new \Error(\sprintf('Undefined property %s::$%s', static::class, $property));
        }
    }

    /**
     * An alias of the {@see $content} property.
     */
    public function getContents(): string
    {
        $target = $this->getTargetSource();

        return $target->content;
    }

    /**
     * An alias of the {@see $isReadable} property.
     */
    public function isReadable(): bool
    {
        return \is_readable($this->pathname);
    }

    /**
     * An alias of the {@see $isExists} property.
     */
    public function isExists(): bool
    {
        return \is_file($this->pathname);
    }

    /**
     * An alias of the {@see $size} property.
     *
     * @return int<0, max>
     * @throws FileNotFoundException When the file does not exist
     * @throws FileNotReadableException When the file cannot be read
     */
    public function getSize(): int
    {
        $size = @\filesize($this->pathname);

        if ($size === false) {
            throw $this->createAccessFailure();
        }

        return \max(0, $size);
    }

    /**
     * An alias of the {@see $size} property.
     *
     * @return int<0, max>
     * @throws FileNotFoundException When the file does not exist
     * @throws FileNotReadableException When the file cannot be read
     */
    public function getModificationTime(): int
    {
        $time = @\filemtime($this->pathname);

        if ($time === false) {
            throw $this->createAccessFailure();
        }

        return \max(0, $time);
    }

    /**
     * Gives the file back, opening it at the first reading of it.
     *
     * @throws FileNotFoundException When the file does not exist
     * @throws FileNotReadableException When the file cannot be opened for reading
     */
    private function getTargetSource(): ResourceSource
    {
        return $this->reader ??= $this->open();
    }

    /**
     * Takes the file over: from here on it belongs to this source, which
     * holds it against being written to until it is given up again.
     *
     * @throws FileNotFoundException When the file does not exist
     * @throws FileNotReadableException When the file cannot be opened for reading
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
        if (!\is_file($this->pathname)) {
            return FileNotFoundException::becauseFileNotFound($this->pathname);
        }

        return FileNotReadableException::becauseFileNotReadable($this->pathname);
    }
}
