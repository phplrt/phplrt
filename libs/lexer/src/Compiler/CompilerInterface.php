<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Compiler;

/**
 * @deprecated since phplrt 3.6 and will be removed in 4.0.
 *
 * @internal This is an internal library interface, please do not use it in your code.
 * @psalm-internal Phplrt\Lexer
 */
interface CompilerInterface
{
    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @return non-empty-string
     */
    public function compile(array $tokens): string;
}
