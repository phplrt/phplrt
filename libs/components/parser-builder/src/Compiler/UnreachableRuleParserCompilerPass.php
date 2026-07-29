<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;

/**
 * Removes the rules that cannot be reached from the initial one.
 *
 * Such rules are dead code: none of them could ever be recognized, so they are
 * dropped instead of being compiled into the parser.
 *
 * Note: Reachability is transitive, so a rule referred to ONLY by an already
 *       unreachable rule is removed as well.
 */
final readonly class UnreachableRuleParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        if ($context->initial === null) {
            return;
        }

        $context->rules = $context->initial->collectRules();
    }
}
