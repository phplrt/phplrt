<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;

/**
 * Checks that token names are unique across ALL lexer states.
 *
 * Token identifiers form a single global space and each named token is
 * exposed as a class constant of the generated lexer, so two states cannot
 * reuse the same name.
 */
final readonly class TokenNameDuplicationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        /** @var array<non-empty-string, true> $names */
        $names = [];

        /**
         * A definition shared between several states is a single token, so it
         * must not be reported as a duplicate of itself.
         *
         * @var \SplObjectStorage<TokenDefinition, true> $visited
         */
        $visited = new \SplObjectStorage();

        $this->validateOrFail($context->tokens, $names, $visited);

        foreach ($context->states as $state) {
            $this->validateOrFail($state, $names, $visited);
        }
    }

    /**
     * @param array<array-key, TokenDefinition> $definitions
     * @param array<non-empty-string, true> $names
     * @param \SplObjectStorage<TokenDefinition, true> $visited
     * @throws CompilationFailedException
     */
    private function validateOrFail(array $definitions, array &$names, \SplObjectStorage $visited): void
    {
        foreach ($definitions as $definition) {
            $name = $definition->name;

            // Skip anonymous tokens
            if ($name === null) {
                continue;
            }

            if (isset($visited[$definition])) {
                continue;
            }

            $visited[$definition] = true;

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
