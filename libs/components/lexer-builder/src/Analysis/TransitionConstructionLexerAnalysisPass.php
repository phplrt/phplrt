<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Analysis;

use Phplrt\Lexer\Builder\Definition\TransitionType;

/**
 * Describes the state transition each token triggers.
 *
 * A token entering a state is described by the name of that state, while the
 * one leaving the current state is described by {@see null}.
 */
final readonly class TransitionConstructionLexerAnalysisPass implements
    LexerAnalysisPassInterface
{
    public function process(LexerResultContext $context): void
    {
        $result = [];

        foreach ([$context->tokens, ...\array_values($context->states)] as $definitions) {
            foreach ($definitions as $id => $definition) {
                $transition = $definition->transition;

                if ($transition === null) {
                    continue;
                }

                $result[$id] = $transition->type === TransitionType::Enter
                    ? $transition->state
                    : null;
            }
        }

        $context->transitions = $result;
    }
}
