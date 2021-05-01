<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Internal\Regex;

/**
 * @internal CompilerInterface is an internal library interface, please do not use it in your code.
 * @psalm-internal Phplrt\Lexer
 */
interface CompilerInterface
{
    /**
     * @param array<string, string> $tokens
     * @return string
     */
    public function compile(array $tokens): string;
}
