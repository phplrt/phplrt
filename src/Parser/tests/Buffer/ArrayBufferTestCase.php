<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Buffer;

use Phplrt\Parser\Buffer\ArrayBuffer;
use Phplrt\Parser\Buffer\BufferInterface;

class ArrayBufferTestCase extends BufferTestCase
{
    /**
     * @param iterable $tokens
     * @return BufferInterface
     */
    protected function create(iterable $tokens): BufferInterface
    {
        return new ArrayBuffer($tokens);
    }
}
