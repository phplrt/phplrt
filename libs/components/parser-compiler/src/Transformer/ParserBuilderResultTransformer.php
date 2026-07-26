<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Transformer;

use Phplrt\Compiler\Parser\Analysis\ParserResultContext;
use Phplrt\Compiler\Parser\ParserBuilderResult;

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
            startTokens: $context->startTokens,
            matchesEmptyInput: $context->matchesEmptyInput,
            presentInTree: $context->presentInTree,
            reducers: $context->reducers,
            constants: $context->constants,
        );
    }
}
