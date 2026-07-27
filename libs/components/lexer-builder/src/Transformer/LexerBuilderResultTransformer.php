<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Transformer;

use Phplrt\Lexer\Builder\Analysis\LexerResultContext;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;
use Phplrt\Lexer\Builder\LexerBuilderResult;

/**
 * Closes the compilation, turning the context the analysis passes were free to
 * complement into the result nothing may change anymore.
 */
final readonly class LexerBuilderResultTransformer
{
    public function transform(LexerResultContext $context): LexerBuilderResult
    {
        if ($context->pattern === '') {
            throw new LexerCompilerException('Pattern must not be empty');
        }

        return new LexerBuilderResult(
            tokens: $context->tokens,
            states: $context->states,
            embeddedStates: $context->embeddedStates,
            pattern: $context->pattern,
            statePatterns: $context->statePatterns,
            channels: $context->channels,
            names: $context->names,
            transitions: $context->transitions,
        );
    }
}
