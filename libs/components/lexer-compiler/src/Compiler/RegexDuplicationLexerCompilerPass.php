<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Definition\ValueTokenDefinition;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;

/**
 * Checks that there are no duplicate patterns for token definitions
 */
final readonly class RegexDuplicationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        $this->validateOrFail($context->tokens);

        foreach ($context->states as $state => $group) {
            $this->validateOrFail($group);
        }
    }

    /**
     * @param array<array-key, TokenDefinition> $definitions
     * @throws CompilationFailedException
     */
    private function validateOrFail(array $definitions): void
    {
        $patterns = [];

        foreach ($definitions as $definition) {
            $regex = match (true) {
                $definition instanceof RegexTokenDefinition => \addcslashes($definition->regex, '/'),
                $definition instanceof ValueTokenDefinition => \preg_quote($definition->value, '/'),
                default => null,
            };

            if ($regex === null) {
                continue;
            }

            if (!isset($patterns[$regex])) {
                $patterns[$regex] = true;
                continue;
            }

            throw new CompilationFailedException($definition, \sprintf(
                'Another token definition %s with the same regex has already been defined previously',
                $regex,
            ));
        }
    }
}
