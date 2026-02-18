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

            $identifier = \sprintf('%s(%s)', $definition->namespace, $regex);

            if (isset($patterns[$identifier])) {
                throw new CompilationFailedException(\sprintf('Definition %s is not unique', $definition));
            }

            $patterns[$identifier] = true;
        }
    }
}
