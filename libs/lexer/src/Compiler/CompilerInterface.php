<?php

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
