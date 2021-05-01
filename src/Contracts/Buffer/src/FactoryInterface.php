<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

interface FactoryInterface
{
    /**
     * @param iterable<TokenInterface> $tokens
     * @return BufferInterface
     */
    public function create(iterable $tokens): BufferInterface;
}
