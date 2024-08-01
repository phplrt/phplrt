<?php

declare(strict_types=1);

namespace Phplrt\Buffer;

final class Factory implements FactoryInterface
{
    public function create(iterable $tokens, ?int $size = null): BufferInterface
    {
        if ($size === null) {
            return new ArrayBuffer($tokens);
        }

        if ($size > 0) {
            return new ExtrusiveBuffer($tokens, $size);
        }

        return new LazyBuffer($tokens);
    }
}
