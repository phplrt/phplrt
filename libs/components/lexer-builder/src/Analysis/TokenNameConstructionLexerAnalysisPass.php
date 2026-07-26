<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Analysis;

/**
 * Describes the name each token is reported under.
 *
 * A token needs no name to be recognized, so the nameless ones are reported by
 * their identifier and are not described here.
 */
final readonly class TokenNameConstructionLexerAnalysisPass implements
    LexerAnalysisPassInterface
{
    public function process(LexerResultContext $context): void
    {
        $result = [];

        foreach ([$context->tokens, ...\array_values($context->states)] as $definitions) {
            foreach ($definitions as $id => $definition) {
                if ($definition->name === null) {
                    continue;
                }

                $result[$id] = $definition->name;
            }
        }

        $context->names = $result;
    }
}
