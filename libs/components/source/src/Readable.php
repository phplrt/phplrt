<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An arbitrary object that supports reading of source data
 */
abstract class Readable implements ReadableInterface
{
    /**
     * @template TArgSource of ReadableInterface
     * @param TArgSource $source
     * @return ($source is ReadableInterface ? TArgSource : ReadableInterface)
     * @throws SourceExceptionInterface
     */
    #[\Deprecated('Please use "SourceFactory::create()" instead', since: '4.0')]
    public static function new(mixed $source): ReadableInterface
    {
        return SourceFactory::createDefault()
            ->create($source);
    }

    /**
     * @api
     *
     * @param non-empty-string|null $pathname
     * @return ($pathname is null ? StringSource : VirtualSource)
     */
    #[\Deprecated('Please use "StringSource::createEmpty()" or "VirtualSource::createEmpty()" instead', since: '4.0')]
    public static function empty(?string $pathname = null): Readable
    {
        if ($pathname === null) {
            return StringSource::createEmpty();
        }

        return VirtualSource::createEmpty($pathname);
    }

    /**
     * @api
     *
     * @param non-empty-string $pathname
     */
    #[\Deprecated('Please use "FileSource::createFromPathname()" instead', since: '4.0')]
    public static function fromPathname(string $pathname): FileSource
    {
        return FileSource::createFromPathname($pathname);
    }

    /**
     * @api
     */
    #[\Deprecated('Please use "FileSource::createFromSplFileInfo()" instead', since: '4.0')]
    public static function fromSplFileInfo(\SplFileInfo $info): FileSource
    {
        return FileSource::createFromSplFileInfo($info);
    }

    /**
     * @api
     *
     * @param non-empty-string|null $pathname
     * @return ($pathname is null ? StringSource : VirtualSource)
     */
    #[\Deprecated('Please use "StringSource::createFromString()" or "VirtualSource::createFromString()" instead', since: '4.0')]
    public static function fromSources(string $sources, ?string $pathname = null): Readable
    {
        if ($pathname === null) {
            return StringSource::createFromString($sources);
        }

        return VirtualSource::createFromString($pathname, $sources);
    }

    /**
     * @api
     *
     * @param resource $resource
     * @param non-empty-string|null $pathname
     * @return ($pathname is null ? ResourceSource : VirtualSource)
     */
    #[\Deprecated('Please use "ResourceSource::createFromResource()" or "VirtualSource::createFromResource()" instead', since: '4.0')]
    public static function fromResource(mixed $resource, ?string $pathname = null): Readable
    {
        if ($pathname === null) {
            return ResourceSource::createFromResource($resource);
        }

        return VirtualSource::createFromResourceStream($pathname, $resource);
    }
}
