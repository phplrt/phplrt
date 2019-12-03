<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

/**
 * Interface ReadableInterface
 */
interface ReadableInterface
{
    /**
     * @return resource
     */
    public function getStream();

    /**
     * @return string
     */
    public function getContents(): string;
}
