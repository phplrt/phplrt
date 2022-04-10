<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

interface ReadableInterface
{
    /**
     * Returns the resource stream of the source.
     *
     * @return resource
     */
    public function getStream();

    /**
     * Returns the contents of the source.
     *
     * @return string
     */
    public function getContents(): string;

    /**
     * Returns the identifier (hash) of the source object. If the value of the
     * source content changes, the hash value will also be changed.
     *
     * @return non-empty-string
     */
    public function getHash(): string;
}
