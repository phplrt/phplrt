<?php

declare(strict_types=1);

namespace Phplrt\Buffer;

final class Factory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(iterable $tokens, int $size = null): BufferInterface
    {
        switch (true) {
            case $size === null:
                return new ArrayBuffer($tokens);

            case $size > 0:
                return new ExtrusiveBuffer($tokens, $size);

            default:
                return new LazyBuffer($tokens);
        }
    }
}
