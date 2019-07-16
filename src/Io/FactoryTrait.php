<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

use Phplrt\Contracts\Io\FactoryInterface;
use Phplrt\Contracts\Io\Readable;
use Phplrt\Io\Exception\FileException;
use Phplrt\Io\Exception\NotReadableException;
use Phplrt\Io\File\Physical;
use Phplrt\Io\File\Virtual;
use Psr\Http\Message\StreamInterface;

/**
 * Trait FactoryTrait
 *
 * @mixin FactoryInterface
 */
trait FactoryTrait
{
    /**
     * {@inheritDoc}
     */
    public static function fromSplFileInfo(\SplFileInfo $info): Readable
    {
        return static::fromPathname($info->getPathname());
    }

    /**
     * {@inheritDoc}
     */
    public static function fromPathname(string $path): Readable
    {
        return new Physical($path);
    }

    /**
     * {@inheritDoc}
     */
    public static function fromSources(string $sources = '', string $name = null): Readable
    {
        return new Virtual($sources, $name);
    }

    /**
     * {@inheritDoc}
     */
    public static function empty(string $name = null): Readable
    {
        return new Virtual('', $name);
    }

    /**
     * {@inheritDoc}
     */
    public static function fromPsrStream(StreamInterface $stream, string $name = null): Readable
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return new Virtual($stream->getContents(), $name);
    }

    /**
     * @param resource $resource
     * @param string|null $name
     * @return Readable
     */
    public static function fromResource($resource, string $name = null): Readable
    {
        if (self::isClosedResource($resource)) {
            throw new NotReadableException('Can not open for reading already closed resource');
        }

        return new Virtual(\stream_get_contents($resource), $name);
    }

    /**
     * @param resource $resource
     * @return bool
     */
    private static function isClosedResource($resource): bool
    {
        if (\version_compare(\PHP_VERSION, '7.2') >= 1) {
            return \gettype($resource) === 'resource (closed)';
        }

        return \gettype($resource) === 'unknown type';
    }

    /**
     * {@inheritDoc}
     */
    public static function new($sources): Readable
    {
        switch (true) {
            case $sources instanceof Readable:
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

                throw new FileException(\sprintf($message, \gettype($sources)));
        }
    }
}
