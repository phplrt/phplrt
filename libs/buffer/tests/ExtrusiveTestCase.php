<?php

declare(strict_types=1);

namespace Phplrt\Buffer\Tests;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Buffer\ExtrusiveBuffer;

class ExtrusiveTestCase extends TestCase
{
    /**
     * @param iterable $tokens
     * @return BufferInterface
     */
    protected function create(iterable $tokens): BufferInterface
    {
        return new ExtrusiveBuffer($tokens);
    }
}
