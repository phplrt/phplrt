<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;

/**
 * Marks the rule the analysis starts at, in case of none has been marked.
 *
 * The first rule added to the builder is used, so the grammar defined from the
 * top-down needs no marking at all.
 */
final readonly class InitialRuleParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        if ($context->initial !== null) {
            return;
        }

        $context->initial = $context->rules[0] ?? null;
    }
}
