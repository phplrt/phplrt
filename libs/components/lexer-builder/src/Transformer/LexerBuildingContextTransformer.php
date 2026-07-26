<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Transformer;

use Phplrt\Compiler\Lexer\Compiler\LexerBuildingContext;
use Phplrt\Compiler\Lexer\LexerBuilder;

/**
 * Copies the data of the builder into the context the compiler passes work on.
 *
 * The token definitions are shared with the builder on purpose: a definition is
 * what a rule of a parser refers to, so replacing it by a copy would break that
 * link. Everything around them is an array, which PHP copies by value, so a
 * pass rewriting the states does not reach the builder.
 */
final readonly class LexerBuildingContextTransformer
{
    public function transform(LexerBuilder $builder): LexerBuildingContext
    {
        $states = [];

        foreach ($builder->states as $name => $state) {
            $states[$name] = \array_values($state->tokens);
        }

        return new LexerBuildingContext(
            tokens: \array_values($builder->tokens),
            states: $states,
            flags: $builder->flags,
        );
    }
}
