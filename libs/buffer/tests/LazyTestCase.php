<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Buffer\Tests;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Buffer\LazyBuffer;

class LazyTestCase extends TestCase
{
    /**
     * @param iterable $tokens
     * @return BufferInterface
     */
    protected function create(iterable $tokens): BufferInterface
    {
        return new LazyBuffer($tokens);
    }
}
