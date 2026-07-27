<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Exception;

use Phplrt\Lexer\Builder\Definition\Definition;

class CompilationFailedException extends LexerCompilerException
{
    public function __construct(
        public Definition $definition,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $definition->context, $code, $previous);
    }
}
