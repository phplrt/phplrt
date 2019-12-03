<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

/**
 * Interface CompilerInterface
 */
interface CompilerInterface
{
    /**
     * @param array $tokens
     * @return string
     */
    public function compile(array $tokens): string;
}
