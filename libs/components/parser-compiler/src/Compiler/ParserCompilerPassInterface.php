<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;

/**
 * Interface that must be implemented by compilation passes.
 */
interface ParserCompilerPassInterface
{
    /**
     * You can rewrite and check the rules of the grammar here before the
     * identifiers are assigned to them.
     *
     * @throws \Throwable in case of any error
     */
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void;
}
