<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\TransitionType;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;
use Phplrt\Compiler\Lexer\LexerBuilder;

final readonly class TokenTransitionValidationLexerCompilerPass implements LexerCompilerPassInterface
{
    public function process(LexerBuilder $builder): void
    {
        $namespaces = [];

        // Collect all namespaces
        foreach ($builder->tokens as $token) {
            if ($token->state === null) {
                continue;
            }

            $namespaces[$token->state] = true;
        }

        // Validate token transitions
        foreach ($builder->tokens as $token) {
            $transition = $token->transition;

            if ($transition === null) {
                continue;
            }

            if ($transition instanceof TransitionType) {
                $transition = null;
            }

            // Check transition existence
            if (\is_string($transition) && !isset($namespaces[$transition])) {
                throw new CompilationFailedException(\sprintf(
                    'Token %s uses an undefined state transition "%s"',
                    $token,
                    $transition,
                ));
            }

            // Check transition recursion
            if ($transition === $token->state) {
                throw new CompilationFailedException(\sprintf(
                    'Token %s uses transition to the same state',
                    $token,
                ));
            }
        }
    }
}
