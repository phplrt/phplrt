<?php

declare(strict_types=1);

namespace Phplrt\Source\Internal;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Factory\StreamSourceFactory;

/**
 * @internal
 */
trait StreamSourceFactoryProvider
{
    /**
     * @var SourceFactoryInterface<resource>|null
     */
    private static ?SourceFactoryInterface $streamSourceFactory;

    /**
     * Implements logic similar to {@see SourceFactoryProvider::getSourceFactory()},
     * only for string sources.
     *
     * @api
     *
     * @return SourceFactoryInterface<resource>
     */
    final public static function getStreamSourceFactory(): SourceFactoryInterface
    {
        return self::$streamSourceFactory ??= new StreamSourceFactory();
    }

    /**
     * Implements logic similar to {@see SourceFactoryProvider::getSourceFactory()},
     * only for string sources.
     *
     * @api
     *
     * @param SourceFactoryInterface<resource> $factory
     * @param \Closure():void $then
     */
    final public static function withStreamSourceFactory(SourceFactoryInterface $factory, \Closure $then): void
    {
        [$previous, self::$streamSourceFactory] = [self::getStreamSourceFactory(), $factory];

        try {
            $then();
        } finally {
            self::$streamSourceFactory = $previous;
        }
    }

    /**
     * @api
     *
     * @param resource $stream
     * @param non-empty-string|null $virtualPathname
     *
     * @phpstan-return ($virtualPathname is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface
     */
    final public static function fromStream(mixed $stream, ?string $virtualPathname = null): ReadableInterface
    {
        $factory = self::getStreamSourceFactory();

        return $factory->create($stream, $virtualPathname);
    }

    /**
     * @api
     *
     * @param resource $resource
     * @param non-empty-string|null $virtualPathname
     *
     * @phpstan-return ($virtualPathname is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface
     */
    #[\Deprecated('Please use {@see self::fromStream()} instead', since: '4.0.0')]
    final public static function fromResource(mixed $resource, ?string $virtualPathname = null): ReadableInterface
    {
        return self::fromStream($resource, $virtualPathname);
    }
}
