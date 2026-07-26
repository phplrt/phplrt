<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Marks the rule the analysis starts at, in case of none has been marked.
 *
 * The first rule added to the builder is used, so the grammar defined from the
 * top-down needs no marking at all.
 */
final readonly class InitialRuleParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        if ($builder->initial !== null) {
            return;
        }

        foreach ($builder->declarations as $rule) {
            $builder->setInitialRule($rule);

            return;
        }
    }
}
