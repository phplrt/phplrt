<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Implementing a virtual (non-real) file over an arbitrary source
 *
 * The pathname is the only thing this object adds: everything that is read
 * comes from the source it has been given, whatever kind it is.
 *
 * @final please do not inherit from this class
 */
class VirtualSource extends Readable implements FileInterface
{
    /**
     * @throws SourceExceptionInterface may occur when it is not possible to
     *         read source's data and/or convert it to a string
     */
    public string $content {
        get => $this->source->content;
    }

    public function __construct(
        /**
         * The virtual file pathname
         *
         * @var non-empty-string
         */
        public readonly string $pathname,
        /**
         * The source everything is read from
         */
        private readonly ReadableInterface $source,
    ) {}

    /**
     * @api
     *
     * @param non-empty-string $pathname
     * @param resource $stream
     */
    public static function createFromResourceStream(string $pathname, mixed $stream): self
    {
        return new self($pathname, new ResourceSource($stream, false));
    }

    /**
     * @api
     *
     * @param non-empty-string $pathname
     */
    public static function createEmpty(string $pathname): self
    {
        return new self($pathname, new StringSource());
    }

    /**
     * @api
     *
     * @param non-empty-string $pathname
     */
    public static function createFromString(string $pathname, string $content): self
    {
        return new self($pathname, new StringSource($content));
    }

    /**
     * @api
     *
     * @param non-empty-string $pathname
     */
    public static function createFromPathname(string $pathname): self
    {
        return new self($pathname, new FileSource($pathname));
    }

    /**
     * @throws SourceExceptionInterface may occur during the inability to read
     *         the source
     */
    public function read(int $offset, int $bytes): string
    {
        return $this->source->read($offset, $bytes);
    }
}
