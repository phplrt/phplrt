<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

/**
 * Interface that must be implemented by compilation passes.
 */
interface LexerCompilerPassInterface
{
    /**
     * You can rewrite and check the token definitions here before the
     * identifiers are assigned to them.
     *
     * @throws \Throwable in case of any error
     */
    public function process(LexerBuildingContext $context): void;
}
