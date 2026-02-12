<?php

declare(strict_types=1);

namespace Phplrt\Source\Internal;

use Phplrt\Contracts\Source\Exception\SourceCreationExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Factory\FileSourceFactory;

/**
 * @internal
 */
trait FileSourceFactoryProvider
{
    /**
     * @var SourceFactoryInterface<\SplFileInfo>|null
     */
    private static ?SourceFactoryInterface $fileSourceFactory;

    /**
     * Implements logic similar to {@see SourceFactoryProvider::getSourceFactory()},
     * only for file ({@see \SplFileInfo}) sources.
     *
     * @api
     *
     * @return SourceFactoryInterface<\SplFileInfo>
     */
    final public static function getFileSourceFactory(): SourceFactoryInterface
    {
        return self::$fileSourceFactory ??= new FileSourceFactory();
    }

    /**
     * Implements logic similar to {@see SourceFactoryProvider::getSourceFactory()},
     * only for file ({@see \SplFileInfo}) sources.
     *
     * @api
     *
     * @param SourceFactoryInterface<\SplFileInfo> $factory
     * @param \Closure():void $then
     */
    final public static function withFileSourceFactory(SourceFactoryInterface $factory, \Closure $then): void
    {
        [$previous, self::$fileSourceFactory] = [self::getFileSourceFactory(), $factory];

        try {
            $then();
        } finally {
            self::$fileSourceFactory = $previous;
        }
    }

    /**
     * @api
     *
     * @throws SourceExceptionInterface
     */
    public static function fromPathname(string $pathname): FileInterface
    {
        return self::fromSplFileInfo(new \SplFileInfo($pathname));
    }

    /**
     * @api
     *
     * @throws SourceCreationExceptionInterface
     */
    final public static function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        $factory = self::getFileSourceFactory();

        $result = $factory->create($info);

        assert($result instanceof FileInterface);

        return $result;
    }
}
