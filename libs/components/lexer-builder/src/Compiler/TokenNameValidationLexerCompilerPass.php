<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;

/**
 * Checks that the token name is valid
 */
final readonly class TokenNameValidationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        $this->validateOrFail($context->tokens);

        foreach ($context->states as $state) {
            $this->validateOrFail($state);
        }
    }

    /**
     * @param array<array-key, TokenDefinition> $definitions
     * @throws CompilationFailedException
     */
    private function validateOrFail(array $definitions): void
    {
        foreach ($definitions as $definition) {
            if ($definition->name === null) {
                continue;
            }

            /** @phpstan-ignore-next-line Additional assertion */
            if ($definition->name === '') {
                throw new CompilationFailedException($definition, 'Token name cannot be empty');
            }
        }
    }
}
