<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Stream;

use Phplrt\Io\Exception\NotAccessibleException;

/**
 * Trait StreamFactoryTrait
 *
 * @mixin Stream
 */
trait FactoryTrait
{
    /**
     * @param string $content
     * @return StreamInterface|static
     * @throws \Throwable
     */
    public static function fromContent(string $content): StreamInterface
    {
        $memory = self::open('php://memory', 'rb+');

        if (@\fwrite($memory, $content) === false) {
            throw new NotAccessibleException('Can not write content data');
        }

        if (@\rewind($memory) === false) {
            throw new NotAccessibleException('Memory data is not rewindable');
        }

        return static::fromResource($memory);
    }

    /**
     * @param string $file
     * @param string $opt
     * @param array $args
     * @return resource
     */
    private static function open(string $file, string $opt, ...$args)
    {
        $resource = @\fopen($file, $opt, ...$args);

        if ($resource === false) {
            $error = \sprintf('Can not open %s', $file);
            throw new NotAccessibleException($error);
        }

        return $resource;
    }

    /**
     * @param resource $resource
     * @return StreamInterface|static
     */
    public static function fromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }

    /**
     * @param string $pathname
     * @param array $options
     * @return StreamInterface|static
     */
    public static function fromPathname(string $pathname, array $options = []): StreamInterface
    {
        $resource = self::open($pathname, 'rb', false, \stream_context_create($options));

        return static::fromResource($resource);
    }
}
