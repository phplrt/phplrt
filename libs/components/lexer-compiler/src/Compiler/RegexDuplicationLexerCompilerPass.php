<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\ValueTokenDefinition;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;
use Phplrt\Compiler\Lexer\LexerBuilder;

final readonly class RegexDuplicationLexerCompilerPass implements LexerCompilerPassInterface
{
    public function process(LexerBuilder $builder): void
    {
        $patterns = [];

        foreach ($builder->tokens as $definition) {
            $regex = match (true) {
                $definition instanceof RegexTokenDefinition => \addcslashes($definition->regex, '/'),
                $definition instanceof ValueTokenDefinition => \preg_quote($definition->value, '/'),
                default => null,
            };

            if ($regex === null) {
                continue;
            }

            if (isset($patterns[$regex])) {
                throw new CompilationFailedException(\sprintf('Definition %s is not unique', $regex));
            }

            $patterns[$regex] = true;
        }
    }
}
