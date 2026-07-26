<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TransitionType;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;

/**
 * Checks that the lexer's state transitions are consistent.
 */
final readonly class TransitionValidationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        foreach ($context->tokens as $definition) {
            $this->validateRootOrFail($definition);
            $this->validateTargetOrFail($context, $definition);
        }

        foreach ($context->states as $state) {
            foreach ($state as $definition) {
                $this->validateTargetOrFail($context, $definition);
            }
        }
    }

    /**
     * The initial state is the bottom of the state stack, so there is nothing
     * it could return to.
     *
     * @throws CompilationFailedException
     */
    private function validateRootOrFail(TokenDefinition $definition): void
    {
        if ($definition->transition?->type !== TransitionType::Exit) {
            return;
        }

        throw new CompilationFailedException($definition, \sprintf(
            'Token definition %s cannot leave the initial lexer state',
            $definition,
        ));
    }

    /**
     * @throws CompilationFailedException
     */
    private function validateTargetOrFail(LexerBuildingContext $context, TokenDefinition $definition): void
    {
        $transition = $definition->transition;

        if ($transition?->type !== TransitionType::Enter) {
            return;
        }

        /** @var non-empty-string $state */
        $state = $transition->state;

        if (isset($context->states[$state])) {
            return;
        }

        throw new CompilationFailedException($definition, \sprintf(
            'Token definition %s %s, which has not been defined',
            $definition,
            $transition,
        ));
    }
}
