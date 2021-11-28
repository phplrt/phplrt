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

trait FactoryTrait
{
    /**
     * @param non-empty-string|null $pathname
     * @return ReadableInterface
     */
    public static function empty(string $pathname = null): ReadableInterface
    {
        return Factory::getInstance()
            ->fromSource('', $pathname)
        ;
    }

    /**
     * @deprecated Please use {@see FactoryTrait::fromSource()} instead.
     */
    public static function fromSources(string $sources, string $pathname = null): ReadableInterface
    {
        return static::fromSource($sources, $pathname);
    }

    /**
     * @param string $source
     * @param non-empty-string|null $pathname
     * @return ReadableInterface
     */
    public static function fromSource(string $source, string $pathname = null): ReadableInterface
    {
        return Factory::getInstance()
            ->fromSource($source, $pathname)
        ;
    }

    /**
     * @deprecated Please use {@see FactoryTrait::create()} instead.
     */
    public static function new(mixed $source): ReadableInterface
    {
        return static::create($source);
    }

    /**
     * @param mixed $source
     * @return ReadableInterface
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public static function create(mixed $source): ReadableInterface
    {
        return Factory::getInstance()
            ->create($source)
        ;
    }

    /**
     * @param \SplFileInfo $info
     * @return FileInterface
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public static function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        return Factory::getInstance()
            ->fromSplFileInfo($info)
        ;
    }

    /**
     * @param non-empty-string $pathname
     * @return FileInterface
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public static function fromPathname(string $pathname): FileInterface
    {
        return Factory::getInstance()
            ->fromPathname($pathname)
        ;
    }

    /**
     * @deprecated Please use {@see FactoryTrait::fromResourceStream()} instead.
     */
    public static function fromResource(mixed $resource, string $pathname = null): ReadableInterface
    {
        return static::fromResourceStream($resource, $pathname);
    }

    /**
     * @param resource $resource
     * @param non-empty-string|null $pathname
     * @return ReadableInterface
     * @throws NotReadableException
     */
    public static function fromResourceStream(mixed $resource, string $pathname = null): ReadableInterface
    {
        return Factory::getInstance()
            ->fromResourceStream($resource, $pathname)
        ;
    }
}
