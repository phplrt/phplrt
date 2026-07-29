<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\TransitionType;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;

/**
 * Checks that the lexer hands the reading over to the lexers it knows.
 */
final readonly class TransitionValidationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        foreach ($context->tokens as $definition) {
            $this->validateExitOrFail($context, $definition);
            $this->validateTargetOrFail($context, $definition);
        }
    }

    /**
     * The outermost lexer is called by nobody, so there is nothing it could
     * give the control back to.
     *
     * @throws CompilationFailedException
     */
    private function validateExitOrFail(LexerBuildingContext $context, TokenDefinition $definition): void
    {
        if ($context->isEmbedded || $definition->transition?->type !== TransitionType::Exit) {
            return;
        }

        throw new CompilationFailedException($definition, \sprintf(
            'Token definition %s cannot end the reading of a lexer no other lexer calls',
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

        /** @var non-empty-string $lexer */
        $lexer = $transition->lexer;

        if (isset($context->lexers[$lexer])) {
            return;
        }

        throw new CompilationFailedException($definition, \sprintf(
            'Token definition %s %s, which has not been defined',
            $definition,
            $transition,
        ));
    }
}
