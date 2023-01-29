<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Compiler;

interface CompilerInterface
{
    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @return non-empty-string
     */
    public function compile(array $tokens): string;
}
