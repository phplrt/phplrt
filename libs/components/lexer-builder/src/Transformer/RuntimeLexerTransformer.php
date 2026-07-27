<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Transformer;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\PhpCodeEmbeddedLexer;
use Phplrt\Lexer\Builder\Definition\Lexer\RuntimeEmbeddedLexer;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;
use Phplrt\Lexer\Builder\LexerBuilderResult;
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
    /**
     * @throws LexerCompilerException in case of a state cannot be read
     */
    public function transform(LexerBuilderResult $result): Lexer
    {
        $states = [];

        foreach ($result->statePatterns as $name => $pattern) {
            $states[$name] = $this->transformState($result, $pattern);
        }

        foreach ($result->embeddedStates as $name => $lexer) {
            $states[$name] = self::transformEmbeddedLexer($name, $lexer);
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

    /**
     * @param non-empty-string $state
     * @throws LexerCompilerException
     */
    private static function transformEmbeddedLexer(string $state, EmbeddedLexerInterface $lexer): LexerInterface
    {
        return match (true) {
            $lexer instanceof RuntimeEmbeddedLexer => $lexer->lexer,
            $lexer instanceof PhpCodeEmbeddedLexer => self::compileEmbeddedLexer($state, $lexer),
        };
    }

    /**
     * Compiles the expression a state is read by into the lexer itself.
     *
     * The compilation is done out of the object context, so that the code
     * cannot reach the transformer itself.
     *
     * @param non-empty-string $state
     * @throws LexerCompilerException
     */
    private static function compileEmbeddedLexer(string $state, PhpCodeEmbeddedLexer $lexer): LexerInterface
    {
        try {
            $result = eval(\sprintf('return %s;', $lexer->code));
        } catch (\ParseError $e) {
            throw LexerCompilerException::becauseEmbeddedLexerIsMalformed($state, $e);
        }

        if (!$result instanceof LexerInterface) {
            throw LexerCompilerException::becauseEmbeddedLexerIsInvalid($state, \get_debug_type($result));
        }

        return $result;
    }
}
