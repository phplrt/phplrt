<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;

/**
 * Checks that rule names are unique.
 *
 * Each named rule is exposed as a class constant of the generated parser, so
 * two rules cannot reuse the same name.
 */
final readonly class RuleNameDuplicationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        /** @var array<non-empty-string, true> $names */
        $names = [];

        foreach ($context->rules as $rule) {
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
