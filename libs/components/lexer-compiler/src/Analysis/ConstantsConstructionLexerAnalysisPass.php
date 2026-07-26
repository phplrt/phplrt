<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Analysis;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;

/**
 * Describes the identifier each named token is available under.
 *
 * A name defined in several states refers to the token of the state that has
 * been assembled last.
 */
final readonly class ConstantsConstructionLexerAnalysisPass implements
    LexerAnalysisPassInterface
{
    public function process(LexerResultContext $context): void
    {
        $result = [];

        foreach ([$context->tokens, ...\array_values($context->states)] as $definitions) {
            /** @var TokenDefinition $definition */
            foreach ($definitions as $id => $definition) {
                if ($definition->name === null) {
                    continue;
                }

                $result[$definition->name] = $id;
            }
        }

        $context->constants = $result;
    }
}
