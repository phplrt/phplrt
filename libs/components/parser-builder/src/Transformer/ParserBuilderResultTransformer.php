<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Transformer;

use Phplrt\Parser\Builder\Analysis\ParserResultContext;
use Phplrt\Parser\Builder\ParserBuilderResult;

/**
 * Closes the compilation, turning the context the analysis passes were free to
 * complement into the result nothing may change anymore.
 */
final readonly class ParserBuilderResultTransformer
{
    public function transform(ParserResultContext $context): ParserBuilderResult
    {
        return new ParserBuilderResult(
            grammar: $context->grammar,
            initial: $context->initial,
            lookahead: $context->lookahead,
            presentInTree: $context->presentInTree,
            reducers: $context->reducers,
            constants: $context->constants,
            branchesByToken: $context->branchesByToken,
        );
    }
}
