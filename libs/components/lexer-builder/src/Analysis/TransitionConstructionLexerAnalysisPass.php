<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Analysis;

use Phplrt\Lexer\Builder\Definition\TransitionType;

/**
 * Describes what each token does to the reading.
 *
 * A token handing the reading over is described by the name of the lexer it
 * hands it over to, while the one ending the reading is described by
 * {@see null}.
 */
final readonly class TransitionConstructionLexerAnalysisPass implements
    LexerAnalysisPassInterface
{
    public function process(LexerResultContext $context): void
    {
        $result = [];

        foreach ($context->tokens as $id => $definition) {
            $transition = $definition->transition;

            if ($transition === null) {
                continue;
            }

            $result[$id] = $transition->type === TransitionType::Enter
                ? $transition->lexer
                : null;
        }

        $context->transitions = $result;
    }
}
