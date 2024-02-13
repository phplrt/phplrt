<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;
use Psr\Http\Message\StreamInterface;

trait SourceFactoryTrait
{
    private static ?SourceFactoryInterface $sourceFactory = null;

    public static function setSourceFactory(SourceFactoryInterface $factory): void
    {
        self::$sourceFactory = $factory;
    }

    public static function getSourceFactory(): SourceFactoryInterface
    {
        return self::$sourceFactory ??= new SourceFactory();
    }

    /**
     * @return ($source is \SplFileInfo
     *     ? FileInterface
     *     : ($source is FileInterface
     *         ? FileInterface
     *         : ReadableInterface)
     * )
     *
     * @throws SourceExceptionInterface
     *
     * @psalm-suppress NoValue : Allow any value
     */
    public static function new($source): ReadableInterface
    {
        if ($source instanceof StreamInterface) {
            return static::fromPsrStream($source);
        }

        $factory = self::getSourceFactory();

        return $factory->create($source);
    }

    /**
     * @psalm-taint-sink file $pathname
     *
     * @param non-empty-string|null $pathname
     *
     * @return ($pathname is null ? ReadableInterface : FileInterface)
     * @throws SourceExceptionInterface
     */
    public static function empty(string $pathname = null): ReadableInterface
    {
        return static::fromSources('', $pathname);
    }

    /**
     * @psalm-taint-sink file $pathname
     *
     * @param non-empty-string|null $pathname
     *
     * @return ($pathname is null ? ReadableInterface : FileInterface)
     * @throws SourceExceptionInterface
     */
    public static function fromSources(string $sources, string $pathname = null): ReadableInterface
    {
        $factory = static::getSourceFactory();

        if ($factory instanceof SourceFactory) {
            return $factory->createFromString($sources, $pathname);
        }

        if ($pathname === null) {
            return new Source($sources);
        }

        return new VirtualFile($pathname, $sources);
    }

    /**
     * @throws SourceExceptionInterface
     */
    public static function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        return static::fromPathname($info->getPathname());
    }

    /**
     * @param non-empty-string $pathname
     *
     * @throws SourceExceptionInterface
     */
    public static function fromPathname(string $pathname): FileInterface
    {
        $factory = static::getSourceFactory();

        if ($factory instanceof SourceFactory) {
            return $factory->createFromFile($pathname);
        }

        return new File($pathname);
    }

    /**
     * @param non-empty-string|null $pathname
     *
     * @return ($pathname is null ? ReadableInterface : FileInterface)
     * @throws SourceExceptionInterface
     *
     * @deprecated since phplrt 3.4 and will be removed in 4.0, use {@see fromResource()} instead.
     */
    public static function fromPsrStream(StreamInterface $stream, string $pathname = null): ReadableInterface
    {
        trigger_deprecation('phplrt/source', '3.4', <<<'MSG'
            Using "%s::fromPsrStream($stream)" with %s argument is deprecated,
            use "%1$s::fromResource($stream->detach())" instead.
            MSG, static::class, \get_class($stream));

        return static::fromResource($stream->detach(), $pathname);
    }

    /**
     * @param resource $resource
     * @param non-empty-string|null $pathname
     *
     * @return ($pathname is null ? ReadableInterface : FileInterface)
     * @throws SourceExceptionInterface
     */
    public static function fromResource($resource, string $pathname = null): ReadableInterface
    {
        $factory = static::getSourceFactory();

        if ($factory instanceof SourceFactory) {
            return $factory->createFromStream($resource, $pathname);
        }

        if ($pathname === null) {
            return new Stream($resource);
        }

        return new VirtualStreamingFile($pathname, $resource);
    }
}
