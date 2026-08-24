<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\Definition;
use Phplrt\Lexer\Builder\Definition\FragmentDefinition;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;

/**
 * Writes every piece of an expression into the expressions referring to it.
 *
 * A reference is written as "(?&NAME)" and stands for the fragment declared
 * under that name, wrapped so that whatever follows the reference counts the
 * piece as a whole. A name that no fragment is declared under is left alone in
 * case the expression captures a subpattern under it, which is what such a
 * reference means to PCRE itself.
 *
 * The states are reached through the lexer they belong to, so a piece declared
 * once is written into every expression of every state.
 */
final readonly class FragmentResolutionLexerCompilerPass implements
    LexerCompilerPassInterface
{
    /**
     * The reference to a piece of an expression, along with everything an
     * expression escapes, so that a reference spelled after a backslash is
     * read as the characters it is written of.
     *
     * @var non-empty-string
     */
    private const string PATTERN_REFERENCE = '/\\\\.|\(\?&([a-zA-Z_][a-zA-Z0-9_]*+)\)/s';

    /**
     * The name an expression captures a subpattern under.
     *
     * @var non-empty-string
     */
    private const string PATTERN_CAPTURE = '/\(\?P?[<\'](%s)[>\']/';

    public function process(LexerBuildingContext $context): void
    {
        self::expandTokens($context->tokens, $context->fragments);

        /** @var \SplObjectStorage<LexerBuilder, null> $visited */
        $visited = new \SplObjectStorage();

        self::expandLexers($context->lexers, $context->fragments, $visited);
    }

    /**
     * @param array<array-key, TokenDefinition> $tokens
     * @param array<non-empty-string, FragmentDefinition> $fragments
     * @throws CompilationFailedException
     */
    private static function expandTokens(array $tokens, array $fragments): void
    {
        foreach ($tokens as $definition) {
            if (!$definition instanceof RegexTokenDefinition) {
                continue;
            }

            $regex = self::expand($definition->regex, $definition, $fragments);

            if ($regex !== '') {
                $definition->regex = $regex;
            }
        }
    }

    /**
     * @param array<non-empty-string, mixed> $lexers
     * @param array<non-empty-string, FragmentDefinition> $fragments
     * @param \SplObjectStorage<LexerBuilder, null> $visited
     * @throws CompilationFailedException
     */
    private static function expandLexers(array $lexers, array $fragments, \SplObjectStorage $visited): void
    {
        foreach ($lexers as $lexer) {
            // A lexer written by hand recognizes whatever it recognizes
            if (!$lexer instanceof LexerBuilder || $visited->offsetExists($lexer)) {
                continue;
            }

            $visited->offsetSet($lexer);

            // A state knows the pieces of the lexer that has entered it along
            // with the ones it declares on its own
            $known = [...$fragments, ...$lexer->fragments];

            self::expandTokens($lexer->tokens, $known);
            self::expandLexers($lexer->lexers, $known, $visited);
        }
    }

    /**
     * Returns the given expression with every piece it refers to written into
     * it.
     *
     * @param array<non-empty-string, FragmentDefinition> $fragments
     * @param array<non-empty-string, true> $writing the pieces the expression
     *        is being written into
     * @throws CompilationFailedException
     */
    private static function expand(
        string $pattern,
        Definition $subject,
        array $fragments,
        array $writing = [],
    ): string {
        $result = \preg_replace_callback(
            pattern: self::PATTERN_REFERENCE,
            callback: static fn(array $matches): string => self::expandReference(
                matches: $matches,
                pattern: $pattern,
                subject: $subject,
                fragments: $fragments,
                writing: $writing,
            ),
            subject: $pattern,
        );

        return $result ?? $pattern;
    }

    /**
     * @param array<int, string> $matches
     * @param array<non-empty-string, FragmentDefinition> $fragments
     * @param array<non-empty-string, true> $writing
     * @throws CompilationFailedException
     */
    private static function expandReference(
        array $matches,
        string $pattern,
        Definition $subject,
        array $fragments,
        array $writing,
    ): string {
        $name = $matches[1] ?? '';

        // Anything an expression escapes is left as it is written
        if ($name === '') {
            return $matches[0];
        }

        $fragment = $fragments[$name] ?? null;

        if ($fragment === null) {
            if (self::capturesSubpattern($pattern, $name)) {
                return $matches[0];
            }

            throw new CompilationFailedException($subject, \sprintf(
                'The %s expression refers to the "%s" fragment, which has not been declared',
                $subject,
                $name,
            ));
        }

        if (isset($writing[$name])) {
            throw new CompilationFailedException($fragment, \sprintf(
                'The %s fragment is written of itself',
                $fragment,
            ));
        }

        return '(?:' . self::expand(
            pattern: $fragment->pattern,
            subject: $fragment,
            fragments: $fragments,
            writing: [...$writing, $name => true],
        ) . ')';
    }

    /**
     * Returns {@see true} in case of the given expression captures a
     * subpattern under the given name, which is what a reference to it means
     * to PCRE.
     *
     * @param non-empty-string $name
     */
    private static function capturesSubpattern(string $pattern, string $name): bool
    {
        $capture = \sprintf(self::PATTERN_CAPTURE, \preg_quote($name, '/'));

        return \preg_match($capture, $pattern) === 1;
    }
}
