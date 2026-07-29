<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;

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
