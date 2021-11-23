<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Buffer;

use Phplrt\Contracts\Buffer\BufferInterface;
use Phplrt\Contracts\Buffer\FactoryInterface;

final class Factory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(iterable $tokens, int $size = null): BufferInterface
    {
        return match (true) {
            $size === null => new ArrayBuffer($tokens),
            $size > 0 => new ExtrusiveBuffer($tokens, $size),
            default => new LazyBuffer($tokens),
        };
    }
}
