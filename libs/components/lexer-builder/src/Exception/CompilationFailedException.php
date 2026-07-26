<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Exception;

use Phplrt\Compiler\Lexer\Definition\Definition;

class CompilationFailedException extends LexerCompilerException
{
    public function __construct(
        public Definition $definition,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
