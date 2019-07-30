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
use Psr\Http\Message\StreamInterface;
use Phplrt\Source\File\VirtualStream;
use Phplrt\Source\File\VirtualContent;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Contracts\Source\Exception\NotReadableExceptionInterface;

/**
 * Trait FactoryTrait
 */
trait FactoryTrait
{
    /**
     * @param string|null $pathName
     * @return ReadableInterface
     */
    public static function empty(string $pathName = null): ReadableInterface
    {
        return static::fromSources('', $pathName);
    }

    /**
     * @param string $sources
     * @param string $pathName
     * @return ReadableInterface|FileInterface
     */
    public static function fromSources(string $sources, string $pathName = null): ReadableInterface
    {
        return $pathName ? new VirtualContent($pathName, $sources) : new Content($sources);
    }

    /**
     * @param mixed $sources
     * @return ReadableInterface|FileInterface
     * @throws NotReadableExceptionInterface
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
     */
    public static function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        return static::fromPathname($info->getPathname());
    }

    /**
     * @param string $pathName
     * @return FileInterface
     */
    public static function fromPathName(string $pathName): FileInterface
    {
        return new Physical($pathName);
    }

    /**
     * @param StreamInterface $stream
     * @param string|null $pathName
     * @return ReadableInterface
     * @throws \RuntimeException
     */
    public static function fromPsrStream(StreamInterface $stream, string $pathName = null): ReadableInterface
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return static::fromResource($stream->detach(), $pathName);
    }

    /**
     * @param resource $resource
     * @param string|null $pathName
     * @return ReadableInterface
     * @throws NotReadableException
     */
    public static function fromResource($resource, string $pathName = null): ReadableInterface
    {
        if (self::isClosedResource($resource)) {
            throw new NotReadableException('Can not open for reading already closed resource');
        }

        return $pathName ? new VirtualStream($pathName, $resource) : new Stream($resource);
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
