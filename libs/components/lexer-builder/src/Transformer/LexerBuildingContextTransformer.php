<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Transformer;

use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\LexerBuilder;

/**
 * Copies the data of the builder into the context the compiler passes work on.
 *
 * The token definitions are shared with the builder on purpose: a definition is
 * what a rule of a parser refers to, so replacing it by a copy would break that
 * link. Everything around them is an array, which PHP copies by value, so a
 * pass rewriting the lexers does not reach the builder.
 */
final readonly class LexerBuildingContextTransformer
{
    public function transform(LexerBuilder $builder): LexerBuildingContext
    {
        return new LexerBuildingContext(
            tokens: \array_values($builder->tokens),
            lexers: $builder->lexers,
            flags: $builder->flags,
            isEmbedded: $builder->isEmbedded,
        );
    }
}
