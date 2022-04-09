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
     * @param iterable<TokenInterface> $tokens
     * @param positive-int|0|null $size
     * @return BufferInterface
     */
    public function create(iterable $tokens, ?int $size = null): BufferInterface;
}
