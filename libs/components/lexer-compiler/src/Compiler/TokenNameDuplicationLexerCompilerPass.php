<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;
use Phplrt\Compiler\Lexer\LexerBuilder;

/**
 * Checks that token names are unique within a single context.
 */
final readonly class TokenNameDuplicationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuilder $builder): void
    {
        $this->validateOrFail($builder->tokens);

        foreach ($builder->states as $state) {
            $this->validateOrFail($state->tokens);
        }
    }

    /**
     * @param array<array-key, TokenDefinition> $definitions
     *
     * @throws CompilationFailedException
     */
    private function validateOrFail(array $definitions): void
    {
        $names = [];

        foreach ($definitions as $definition) {
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

            $names[$name] = $name;
        }
    }
}
