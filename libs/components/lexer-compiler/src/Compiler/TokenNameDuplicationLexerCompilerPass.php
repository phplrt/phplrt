<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;
use Phplrt\Compiler\Lexer\LexerBuilder;

final readonly class TokenNameDuplicationLexerCompilerPass implements LexerCompilerPassInterface
{
    public function process(LexerBuilder $builder): void
    {
        $names = [];

        foreach ($builder->tokens as $token) {
            $name = $token->name;

            // Skip anonymous tokens
            if ($name === null) {
                continue;
            }

            if (isset($names[$name])) {
                throw new CompilationFailedException(\sprintf('Token name of %s is not unique', $token));
            }

            $names[$name] = $name;
        }
    }
}
