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
 * A lexer reading a fragment is compiled the very same way, so the metadata of
 * each of them describes nothing but its own tokens.
 *
 * A name is what a grammar refers to a lexer by, and nothing refers to a lexer
 * once it has been built, so the names are resolved here and the compiled
 * lexer is given the lexers themselves.
 */
final readonly class RuntimeLexerTransformer
{
    /**
     * @throws LexerCompilerException in case of a fragment cannot be read
     */
    public function transform(LexerBuilderResult $result): Lexer
    {
        $lexers = [];

        foreach ($result->lexers as $name => $lexer) {
            $lexers[$name] = $lexer instanceof LexerBuilderResult
                ? $this->transform($lexer)
                : self::transformEmbeddedLexer($name, $lexer);
        }

        return new Lexer(
            pattern: $result->pattern,
            channels: $result->channels,
            names: $result->names,
            transitions: self::transformTransitions($result, $lexers),
        );
    }

    /**
     * @param array<non-empty-string, LexerInterface> $lexers
     * @return array<int, LexerInterface|null>
     * @throws LexerCompilerException
     */
    private static function transformTransitions(LexerBuilderResult $result, array $lexers): array
    {
        $transitions = [];

        foreach ($result->transitions as $id => $name) {
            $transitions[$id] = $name === null
                ? null
                : $lexers[$name] ?? throw LexerCompilerException::becauseLexerIsNotDefined($name);
        }

        return $transitions;
    }

    /**
     * @param non-empty-string $name
     * @throws LexerCompilerException
     */
    private static function transformEmbeddedLexer(string $name, EmbeddedLexerInterface $lexer): LexerInterface
    {
        return match (true) {
            $lexer instanceof RuntimeEmbeddedLexer => $lexer->lexer,
            $lexer instanceof PhpCodeEmbeddedLexer => self::compileEmbeddedLexer($name, $lexer),
        };
    }

    /**
     * Compiles the expression a fragment is read by into the lexer itself.
     *
     * The compilation is done out of the object context, so that the code
     * cannot reach the transformer itself.
     *
     * @param non-empty-string $name
     * @throws LexerCompilerException
     */
    private static function compileEmbeddedLexer(string $name, PhpCodeEmbeddedLexer $lexer): LexerInterface
    {
        try {
            $result = eval(\sprintf('return %s;', $lexer->code));
        } catch (\ParseError $e) {
            throw LexerCompilerException::becauseEmbeddedLexerIsMalformed($name, $e);
        }

        if (!$result instanceof LexerInterface) {
            throw LexerCompilerException::becauseEmbeddedLexerIsInvalid($name, \get_debug_type($result));
        }

        return $result;
    }
}
