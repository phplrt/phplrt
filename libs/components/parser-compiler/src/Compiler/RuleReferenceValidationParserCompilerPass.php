<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Checks that every rule referred to by the grammar is defined in it.
 */
final readonly class RuleReferenceValidationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        foreach ($builder->rules as $rule) {
            foreach ($rule->children as $child) {
                if ($builder->rules->offsetExists($child)) {
                    continue;
                }

                throw new CompilationFailedException($rule, \sprintf(
                    'Rule %s refers to %s, which has not been defined',
                    $rule,
                    $child->printReference(),
                ));
            }
        }
    }
}
