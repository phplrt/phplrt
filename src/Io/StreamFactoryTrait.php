<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

use Phplrt\Exception\ErrorWrapper;

/**
 * Trait StreamFactoryTrait
 * @mixin Stream
 */
trait StreamFactoryTrait
{
    /**
     * @param string $content
     * @return StreamInterface|static
     */
    public static function fromContent(string $content): StreamInterface
    {
        $resource = ErrorWrapper::wrapMany(static function () use ($content) {
            $stream = yield @\fopen('php://memory', 'rb+');

            yield @\fwrite($stream, $content);
            yield @\rewind($stream);

            return $stream;
        });

        return static::fromResource($resource);
    }

    /**
     * @param string $pathname
     * @param array $options
     * @return StreamInterface|static
     */
    public static function fromPathname(string $pathname, array $options = []): StreamInterface
    {
        $resource = ErrorWrapper::wrap(static function () use ($pathname, $options) {
            return @\fopen($pathname, 'rb+', false, \stream_context_create($options));
        });

        return static::fromResource($resource);
    }

    /**
     * @param resource $resource
     * @return StreamInterface|static
     */
    public static function fromResource($resource): StreamInterface
    {
        return new static($resource);
    }
}
