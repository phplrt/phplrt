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
        if ($size <= 0) {
            return new LazyBuffer($tokens);
        }

        if ($size !== null) {
            return new ExtrusiveBuffer($tokens, $size);
        }

        return new ArrayBuffer($tokens);
    }
}
