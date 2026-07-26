<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Interface that must be implemented by compilation passes.
 */
interface ParserCompilerPassInterface
{
    /**
     * You can modify the builder here before it is dumped.
     *
     * @throws \Throwable in case of any error
     */
    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void;
}
