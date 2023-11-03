<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\ContentReader\ContentReader;
use Phplrt\Source\ContentReader\StreamContentReader;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\StreamReader\ContentStreamReader;
use Phplrt\Source\StreamReader\StreamReader;
use Psr\Http\Message\StreamInterface;

trait FactoryTrait
{
    /**
     * @param string|null $pathname
     */
    public static function empty(string $pathname = null): ReadableInterface
    {
        return static::fromSources('', $pathname);
    }

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
     * @param mixed $sources
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
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public static function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        return static::fromPathname($info->getPathname());
    }

    /**
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public static function fromPathname(string $pathname): FileInterface
    {
        File::assertValidPathname($pathname);

        return new File($pathname);
    }

    /**
     * @param string|null $pathname
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
     * @throws NotReadableException
     */
    public static function fromResource($resource, string $pathname = null): ReadableInterface
    {
        if (!StreamUtil::isStream($resource)) {
            $message = 'First argument must be a valid resource, but %s given';

            throw new \InvalidArgumentException(\sprintf($message, \gettype($resource)));
        }

        if (StreamUtil::isClosedStream($resource)) {
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
