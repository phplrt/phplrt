<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilder;

/**
 * Interface that must be implemented by compilation passes.
 */
interface LexerCompilerPassInterface
{
    /**
     * You can modify the builder here before it is dumped.
     *
     * @throws \Throwable in case of any error
     */
    public function process(LexerBuilder $builder): void;
}
