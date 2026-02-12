<?php

declare(strict_types=1);

namespace Phplrt\Source\Internal;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Readable;
use Phplrt\Source\SourceFactory;

/**
 * @internal
 */
trait SourceFactoryProvider
{
    use StringSourceFactoryProvider;
    use StreamSourceFactoryProvider;
    use FileSourceFactoryProvider;

    /**
     * @var SourceFactoryInterface<mixed>|null
     */
    private static ?SourceFactoryInterface $sourceFactory;

    /**
     * Returns the {@see SourceFactoryInterface} factory instance used
     * for all static constructors of the {@see Readable} class.
     *
     * @api
     *
     * @return SourceFactoryInterface<mixed>
     */
    final public static function getSourceFactory(): SourceFactoryInterface
    {
        return self::$sourceFactory ??= SourceFactory::default();
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
     * @param SourceFactoryInterface<mixed> $factory
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

        return $factory->create($source);
    }

    /**
     * @api
     */
    final public static function tryFrom(mixed $source): ?ReadableInterface
    {
        $factory = self::getSourceFactory();

        if ($factory->supports($source)) {
            try {
                return $factory->create($source);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
