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
 * @internal ContentReaderInterface is an internal library interface, please do not use it in your code.
 * @psalm-internal Phplrt\source
 */
interface ContentReaderInterface
{
    /**
     * @return string
     */
    public function getContents(): string;
}
