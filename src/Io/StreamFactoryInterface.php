<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

/**
 * Interface StreamFactoryInterface
 */
interface StreamFactoryInterface
{
    /**
     * @param string $content
     * @return StreamInterface|static
     */
    public static function fromContent(string $content): StreamInterface;

    /**
     * @param string $pathname
     * @param array $options
     * @return StreamInterface|static
     */
    public static function fromPathname(string $pathname, array $options = []): StreamInterface;

    /**
     * @param resource $resource
     * @return StreamInterface|static
     */
    public static function fromResource($resource): StreamInterface;
}
