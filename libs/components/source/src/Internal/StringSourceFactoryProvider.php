<?php

declare(strict_types=1);

namespace Phplrt\Source\Internal;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Factory\StringSourceFactory;

/**
 * @internal
 */
trait StringSourceFactoryProvider
{
    /**
     * @var SourceFactoryInterface<string>|null
     */
    private static ?SourceFactoryInterface $stringSourceFactory;

    /**
     * Implements logic similar to {@see SourceFactoryProvider::getSourceFactory()},
     * only for string sources.
     *
     * @api
     *
     * @return SourceFactoryInterface<string>
     */
    final public static function getStringSourceFactory(): SourceFactoryInterface
    {
        return self::$stringSourceFactory ??= new StringSourceFactory();
    }

    /**
     * Implements logic similar to {@see SourceFactoryProvider::getSourceFactory()},
     * only for string sources.
     *
     * @api
     *
     * @param SourceFactoryInterface<string> $factory
     * @param \Closure():void $then
     */
    final public static function withStringSourceFactory(SourceFactoryInterface $factory, \Closure $then): void
    {
        [$previous, self::$stringSourceFactory] = [self::getStringSourceFactory(), $factory];

        try {
            $then();
        } finally {
            self::$stringSourceFactory = $previous;
        }
    }

    /**
     * @api
     *
     * @param non-empty-string|null $virtualPathname
     *
     * @phpstan-return ReadableInterface
     *
     * @throws SourceExceptionInterface
     */
    final public static function empty(?string $virtualPathname = null): ReadableInterface
    {
        return self::fromString('', $virtualPathname);
    }

    /**
     * @api
     *
     * @param non-empty-string|null $virtualPathname
     *
     * @phpstan-return ReadableInterface
     *
     * @throws SourceExceptionInterface
     */
    final public static function fromString(string $content, ?string $virtualPathname = null): ReadableInterface
    {
        $factory = self::getStringSourceFactory();

        return $factory->create($content, $virtualPathname);
    }

    /**
     * @api
     *
     * @param non-empty-string|null $virtualPathname
     *
     * @phpstan-return ReadableInterface
     *
     * @throws SourceExceptionInterface
     */
    #[\Deprecated('Please use {@see self::fromString()} instead', since: '4.0.0')]
    final public static function fromSources(string $sources, ?string $virtualPathname = null): ReadableInterface
    {
        return self::fromString($sources, $virtualPathname);
    }
}
