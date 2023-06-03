<?php

declare(strict_types=1);

namespace Phplrt\Buffer\Tests;

use Phplrt\Buffer\ArrayBuffer;
use Phplrt\Buffer\BufferInterface;

class ArrayTestCase extends TestCase
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
