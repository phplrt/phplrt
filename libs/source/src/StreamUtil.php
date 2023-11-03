<?php

declare(strict_types=1);

namespace Phplrt\Source;

final class StreamUtil
{
    /**
     * @param resource $stream
     * @return bool
     */
    public static function isStream($stream): bool
    {
        return self::isNonClosedStream($stream) || self::isClosedStream($stream);
    }

    /**
     * @param resource $stream
     * @return bool
     */
    public static function isNonClosedStream($stream): bool
    {
        return \is_resource($stream);
    }

    /**
     * @param resource $stream
     * @return bool
     */
    public static function isClosedStream($stream): bool
    {
        return \gettype($stream) === 'resource (closed)';
    }

    /**
     * @param resource $resource
     * @return array
     */
    public static function serialize($resource): array
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
    public static function unserialize(array $data)
    {
        if (!isset($data['uri'], $data['mode'], $data['seek'])) {
            return \fopen('php://memory', 'rb');
        }

        $resource = \fopen($data['uri'], $data['mode']);
        \fseek($resource, $data['seek']);

        return $resource;
    }
}
