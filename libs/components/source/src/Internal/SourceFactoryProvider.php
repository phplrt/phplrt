<?php

declare(strict_types=1);

namespace Phplrt\Source\Internal;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Readable;
use Phplrt\Source\SourceFactory;

trait SourceFactoryProvider
{
    private static ?SourceFactoryInterface $sourceFactory;

    /**
     * Returns the {@see SourceFactoryInterface} factory instance used
     * for all static constructors of the {@see Readable} class.
     */
    final public static function getSourceFactory(): SourceFactoryInterface
    {
        return self::$sourceFactory ??= new SourceFactory();
    }

    /**
     * Sets the factory for use in all static calls and returns the previous
     * state after the callback process completes.
     *
     * ```
     * File::withSourceFactory($newFactory, function() {
     *     $createdByNewFactory = File::new();
     * });
     *
     * $createdByDefaultFactory = File::new();
     * ```
     *
     * Note that this mechanism protects you from side effects when external
     * code changes the global state (the value of the static {@see $sourceFactory}
     * variable). This state change affects only the nested call trace and does
     * not affect adjacent code.
     *
     * However, despite such precautions, changing the global state (changing
     * the default {@see SourceFactory} implementation) is NOT RECOMMENDED, as
     * it can cause unexpected problems in asynchronous code. It is recommended
     * to use a {@see SourceFactoryInterface} instance inconsistently.
     *
     * @api
     *
     * @param \Closure():void $then
     */
    final public static function withSourceFactory(SourceFactoryInterface $factory, \Closure $then): void
    {
        [$previous, self::$sourceFactory] = [self::getSourceFactory(), $factory];

        try {
            $then();
        } finally {
            self::$sourceFactory = $previous;
        }
    }

    /**
     * @api
     *
     * @throws SourceExceptionInterface
     */
    final public static function new(mixed $source): ReadableInterface
    {
        $factory = self::getSourceFactory();

        return match (true) {
            $source instanceof \SplFileInfo => $factory->createFromFile($source->getPathname()),
            \is_string($source) => $factory->createFromString($source),
            \is_resource($source) => $factory->createFromStream($source),
            default => throw NotCreatableException::becauseSourceIsInvalid($source),
        };
    }

    /**
     * @api
     *
     * @param non-empty-string|null $pathname
     *
     * @phpstan-return ($pathname is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface
     */
    final public static function empty(?string $pathname = null): ReadableInterface
    {
        return self::fromString('', $pathname);
    }

    /**
     * @api
     *
     * @param non-empty-string $pathname
     *
     * @throws SourceExceptionInterface
     */
    final public static function fromFile(string $pathname): FileInterface
    {
        $factory = self::getSourceFactory();

        return $factory->createFromFile($pathname);
    }

    /**
     * @api
     *
     * @deprecated please use {@see self::fromFile()} instead
     *
     * @param non-empty-string $pathname
     *
     * @throws SourceExceptionInterface
     */
    public static function fromPathname(string $pathname): FileInterface
    {
        return self::fromFile($pathname);
    }

    /**
     * @api
     *
     * @throws SourceExceptionInterface
     */
    final public static function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        return self::fromFile($info->getPathname());
    }

    /**
     * @api
     *
     * @param non-empty-string|null $pathname
     *
     * @phpstan-return ($pathname is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface
     */
    final public static function fromString(string $content, ?string $pathname = null): ReadableInterface
    {
        $factory = self::getSourceFactory();

        return $factory->createFromString($content, $pathname);
    }

    /**
     * @api
     *
     * @deprecated please use {@see self::fromString()} instead
     *
     * @param non-empty-string|null $pathname
     *
     * @phpstan-return ($pathname is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface
     */
    final public static function fromSources(string $sources, ?string $pathname = null): ReadableInterface
    {
        return self::fromString($sources, $pathname);
    }

    /**
     * @api
     *
     * @param resource $stream
     * @param non-empty-string|null $pathname
     *
     * @phpstan-return ($pathname is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface
     */
    final public static function fromStream(mixed $stream, ?string $pathname = null): ReadableInterface
    {
        $factory = self::getSourceFactory();

        return $factory->createFromStream($stream, $pathname);
    }

    /**
     * @api
     *
     * @deprecated please use {@see self::fromStream()} instead
     *
     * @param resource $resource
     * @param non-empty-string|null $pathname
     *
     * @phpstan-return ($pathname is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface
     */
    final public static function fromResource(mixed $resource, ?string $pathname = null): ReadableInterface
    {
        return self::fromStream($resource, $pathname);
    }
}
