<?php

declare(strict_types=1);

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

interface FactoryInterface
{
    /**
     * @param iterable<int<0, max>, TokenInterface> $tokens
     * @param int<0, max>|null $size
     */
    public function create(iterable $tokens, ?int $size = null): BufferInterface;
}
