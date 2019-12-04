<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\File\Stream;
use Phplrt\Source\File\Content;
use Phplrt\Source\File\Physical;
use Phplrt\Source\File\VirtualStream;
use Psr\Http\Message\StreamInterface;
use Phplrt\Source\File\VirtualContent;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Exception\NotAccessibleException;

/**
 * Trait FactoryTrait
 */
trait FactoryTrait
{
    /**
     * @param string|null $pathname
     * @return ReadableInterface
     */
    public static function empty(string $pathname = null): ReadableInterface
    {
        return static::fromSources('', $pathname);
    }

    /**
     * @param string $sources
     * @param string $pathname
     * @return ReadableInterface|FileInterface
     */
    public static function fromSources(string $sources, string $pathname = null): ReadableInterface
    {
        return $pathname ? new VirtualContent($pathname, $sources) : new Content($sources);
    }

    /**
     * @param mixed $sources
     * @return ReadableInterface|FileInterface
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public static function new($sources): ReadableInterface
    {
        switch (true) {
            case $sources instanceof ReadableInterface:
                return $sources;

            case $sources instanceof \SplFileInfo:
                return static::fromSplFileInfo($sources);

            case \is_string($sources):
                return static::fromSources($sources);

            case $sources instanceof StreamInterface:
                return static::fromPsrStream($sources);

            case \is_resource($sources):
                return static::fromResource($sources);

            default:
                $message = 'Unrecognized readable file type "%s"';
                throw new \InvalidArgumentException(\sprintf($message, \gettype($sources)));
        }
    }

    /**
     * @param \SplFileInfo $info
     * @return FileInterface
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public static function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        return static::fromPathname($info->getPathname());
    }

    /**
     * @param string $pathname
     * @return FileInterface
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public static function fromPathname(string $pathname): FileInterface
    {
        return new Physical($pathname);
    }

    /**
     * @param StreamInterface $stream
     * @param string|null $pathname
     * @return ReadableInterface
     * @throws \RuntimeException
     */
    public static function fromPsrStream(StreamInterface $stream, string $pathname = null): ReadableInterface
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return static::fromResource($stream->detach(), $pathname);
    }

    /**
     * @param resource $resource
     * @param string|null $pathname
     * @return ReadableInterface
     * @throws NotReadableException
     */
    public static function fromResource($resource, string $pathname = null): ReadableInterface
    {
        if (self::isClosedResource($resource)) {
            throw new NotReadableException('Can not open for reading already closed resource');
        }

        return $pathname ? new VirtualStream($pathname, $resource) : new Stream($resource);
    }

    /**
     * @param resource $resource
     * @return bool
     */
    private static function isClosedResource($resource): bool
    {
        if (\version_compare(\PHP_VERSION, '7.2') >= 1) {
            return \gettype($resource) === 'resource (closed)';
        }

        return \gettype($resource) === 'unknown type';
    }
}
