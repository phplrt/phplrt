<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Transformer;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Lexer;

/**
 * Turns the result of the compilation into the lexer reading the input.
 *
 * The metadata describes the lexer as a whole, and every state is given all of
 * it: a token identifier is unique across all states, and the pattern of a
 * state only ever reports the identifiers of its own tokens, so the entries of
 * the neighbours are unreachable rather than harmful.
 */
final readonly class RuntimeLexerTransformer
{
    public function transform(LexerBuilderResult $result): Lexer
    {
        $states = [];

        foreach ($result->statePatterns as $name => $pattern) {
            $states[$name] = $this->transformState($result, $pattern);
        }

        return $this->transformState($result, $result->pattern, $states);
    }

    /**
     * @param non-empty-string $pattern
     * @param array<non-empty-string, LexerInterface> $states
     */
    private function transformState(LexerBuilderResult $result, string $pattern, array $states = []): Lexer
    {
        return new Lexer(
            pattern: $pattern,
            channels: $result->channels,
            names: $result->names,
            transitions: $result->transitions,
            states: $states,
        );
    }
}
