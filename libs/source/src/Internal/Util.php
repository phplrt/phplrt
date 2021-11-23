<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\Internal;

/**
 * @internal Util is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\source
 */
final class Util
{
    /**
     * @param resource $stream
     * @return bool
     */
    public static function isStream(mixed $stream): bool
    {
        return self::isNonClosedStream($stream) || self::isClosedStream($stream);
    }

    /**
     * @param resource $stream
     * @return bool
     */
    public static function isNonClosedStream(mixed $stream): bool
    {
        return \is_resource($stream);
    }

    /**
     * @param resource $stream
     * @return bool
     */
    public static function isClosedStream(mixed $stream): bool
    {
        return \gettype($stream) === 'resource (closed)';
    }

    /**
     * @param resource $resource
     * @return array
     */
    public static function serialize(mixed $resource): array
    {
        \error_clear_last();

        $meta = @\stream_get_meta_data($resource);

        if (\error_get_last()) {
            return [];
        }

        return [
            'uri'  => $meta['uri'],
            'mode' => $meta['mode'],
            'seek' => $meta['unread_bytes'],
        ];
    }

    /**
     * @param array $data
     * @return resource
     */
    public static function unserialize(array $data): mixed
    {
        if (! isset($data['uri'], $data['mode'], $data['seek'])) {
            return \fopen('php://memory', 'rb');
        }

        $resource = \fopen($data['uri'], $data['mode']);
        \fseek($resource, $data['seek']);

        return $resource;
    }
}
