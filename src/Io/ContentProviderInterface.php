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
 * Interface ContentProviderInterface
 */
interface ContentProviderInterface
{
    /**
     * Returns the full contents of the source.
     *
     * @return string
     */
    public function getContents(): string;

    /**
     * Returns content stream
     *
     * @param array $options Stream context options.
     * @return StreamInterface
     */
    public function getStream(array $options = []): StreamInterface;

    /**
     * Returns content stream
     *
     * @deprecated Since version 1.1. Please use getStream() method instead.
     * @param bool $exclusive Exclusive access to the file means that it
     *      cannot be accessed by other programs while reading the sources.
     * @return resource
     */
    public function getStreamContents(bool $exclusive = false);
}
