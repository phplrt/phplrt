<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Internal\ContentReader\ContentReader;
use Phplrt\Source\Internal\ContentReader\StreamContentReader;
use Phplrt\Source\Internal\StreamReader\ContentStreamReader;
use Phplrt\Source\Internal\StreamReader\StreamReader;
use Phplrt\Source\Internal\Util;
use Psr\Http\Message\StreamInterface;

trait FactoryTrait
{
    /**
     * @param non-empty-string|null $pathname
     * @return ReadableInterface
     */
    public static function empty(string $pathname = null): ReadableInterface
    {
        return static::fromSources('', $pathname);
    }

    /**
     * @param string $sources
     * @param non-empty-string|null $pathname
     * @return ReadableInterface|FileInterface
     */
    public static function fromSources(string $sources, string $pathname = null): ReadableInterface
    {
        $stream = new ContentStreamReader($sources);
        $content = new ContentReader($sources);

        if ($pathname !== null) {
            return new File($pathname, $stream, $content);
        }

        return new Readable($stream, $content);
    }

    /**
     * @param mixed $source
     * @return ReadableInterface|FileInterface
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public static function new(mixed $source): ReadableInterface
    {
        switch (true) {
            case $source instanceof ReadableInterface:
                return $source;

            case $source instanceof \SplFileInfo:
                return static::fromSplFileInfo($source);

            case \is_string($source):
                return static::fromSources($source);

            case $source instanceof StreamInterface:
                return static::fromPsrStream($source);

            case \is_resource($source):
                return static::fromResource($source);

            default:
                $message = 'Unrecognized readable file type "%s"';
                throw new \InvalidArgumentException(\sprintf($message, \get_debug_type($source)));
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
     * @param non-empty-string $pathname
     * @return FileInterface
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public static function fromPathname(string $pathname): FileInterface
    {
        File::assertValidPathname($pathname);

        return new File($pathname);
    }

    /**
     * @param StreamInterface $stream
     * @param non-empty-string|null $pathname
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
     * @param non-empty-string|null $pathname
     * @return ReadableInterface
     * @throws NotReadableException
     */
    public static function fromResource($resource, string $pathname = null): ReadableInterface
    {
        if (! Util::isStream($resource)) {
            $message = 'First argument must be a valid resource, but %s given';

            throw new \InvalidArgumentException(\sprintf($message, \gettype($resource)));
        }

        if (Util::isClosedStream($resource)) {
            throw new NotReadableException('Can not open for reading already closed resource');
        }

        $stream = new StreamReader($resource);
        $content = new StreamContentReader($resource);

        if ($pathname !== null) {
            return new File($pathname, $stream, $content);
        }

        return new Readable($stream, $content);
    }
}
