<?php

declare(strict_types=1);

namespace Phplrt\Buffer\Tests\Unit;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Buffer\LazyBuffer;

class LazyBufferTest extends TestCase
{
    protected function create(iterable $tokens): BufferInterface
    {
        return new LazyBuffer($tokens);
    }
}
