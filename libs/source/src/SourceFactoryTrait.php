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
                $message = \vsprintf('Unrecognized readable file type "%s"', [
                    \get_debug_type($source),
                ]);
                throw new \InvalidArgumentException($message);
        }
    }

    public static function setSourceFactory(SourceFactoryInterface $factory): void
    {
        self::$sourceFactory = $factory;
    }

    public static function getSourceFactory(): SourceFactoryInterface
    {
        return self::$sourceFactory ??= new SourceFactory();
    }

    /**
     * An alternative factory function of the {@see SourceFactoryInterface::create()} method.
     *
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
     * An alternative factory function of the {@see SourceFactoryInterface::create()} method.
     *
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

        return $factory->create($sources, $pathname);
    }

    /**
     * An alternative factory function of the {@see SourceFactoryInterface::createFromFile()} method.
     *
     * @throws SourceExceptionInterface
     */
    public static function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        return static::fromPathname($info->getPathname());
    }

    /**
     * An alternative factory function of the {@see SourceFactoryInterface::createFromFile()} method.
     *
     * @param non-empty-string $pathname
     *
     * @throws SourceExceptionInterface
     */
    public static function fromPathname(string $pathname): FileInterface
    {
        $factory = static::getSourceFactory();

        return $factory->createFromFile($pathname);
    }

    /**
     * An alternative factory function of the {@see SourceFactoryInterface::createFromStream()} method.
     *
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
     * An alternative factory function of the {@see SourceFactoryInterface::createFromStream()} method.
     *
     * @param resource $resource
     * @param non-empty-string|null $name
     *
     * @return ($pathname is null ? ReadableInterface : FileInterface)
     * @throws SourceExceptionInterface
     */
    public static function fromResource($resource, string $name = null): ReadableInterface
    {
        $factory = static::getSourceFactory();

        return $factory->createFromStream($resource, $name);
    }
}
