<?php

declare(strict_types=1);

namespace Phplrt\Buffer\Tests\Unit;

use Phplrt\Buffer\ArrayBuffer;
use Phplrt\Buffer\BufferInterface;

class ArrayBufferTest extends TestCase
{
    protected function create(iterable $tokens): BufferInterface
    {
        return new ArrayBuffer($tokens);
    }
}
