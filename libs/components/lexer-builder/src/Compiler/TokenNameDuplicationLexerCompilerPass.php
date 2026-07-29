<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Exception\CompilationFailedException;

/**
 * Checks that token names are unique within the lexer.
 *
 * Each named token is exposed as a class constant of the generated lexer, so
 * a name cannot be reused. A lexer reading a fragment of its own is a class of
 * its own, which is why its names are none of this one's business.
 */
final readonly class TokenNameDuplicationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        /** @var array<non-empty-string, true> $names */
        $names = [];

        foreach ($context->tokens as $definition) {
            $name = $definition->name;

            // Skip anonymous tokens
            if ($name === null) {
                continue;
            }

            if (isset($names[$name])) {
                throw new CompilationFailedException($definition, \sprintf(
                    'Token name of %s is not unique',
                    $definition,
                ));
            }

            $names[$name] = true;
        }
    }
}
