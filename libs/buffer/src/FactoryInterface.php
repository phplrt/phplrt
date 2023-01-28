<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

interface FactoryInterface
{
    /**
     * @param iterable<int<0, max>, TokenInterface> $tokens
     * @param int<0, max>|null $size
     * @return BufferInterface
     */
    public function create(iterable $tokens, ?int $size = null): BufferInterface;
}
