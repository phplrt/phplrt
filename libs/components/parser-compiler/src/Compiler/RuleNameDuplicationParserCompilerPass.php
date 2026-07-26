<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Checks that rule names are unique.
 *
 * Each named rule is exposed as a class constant of the generated parser, so
 * two rules cannot reuse the same name.
 */
final readonly class RuleNameDuplicationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        /** @var array<non-empty-string, true> $names */
        $names = [];

        foreach ($builder->rules as $rule) {
            $name = $rule->name;

            // Skip anonymous rules
            if ($name === null) {
                continue;
            }

            if (isset($names[$name])) {
                throw new CompilationFailedException($rule, \sprintf(
                    'Rule name of %s is not unique',
                    $rule,
                ));
            }

            $names[$name] = true;
        }
    }
}
