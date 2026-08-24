<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\ValueTokenDefinition;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;

/**
 * Checks that no two token definitions recognize the same fragment
 */
final readonly class RegexDuplicationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    /**
     * An expression written of nothing that stands for more than itself, which
     * is what recognizing a fragment as it is written looks like.
     *
     * @var non-empty-string
     */
    private const string PATTERN_LITERAL = '/^(?:\\\\[^\p{L}\p{N}]|[^\\\\^$.\[\]|()?*+{}])++$/u';

    /**
     * Everything such an expression escapes.
     *
     * @var non-empty-string
     */
    private const string PATTERN_ESCAPE = '/\\\\(.)/su';

    public function process(LexerBuildingContext $context): void
    {
        $this->validateOrFail($context->tokens);
    }

    /**
     * @param array<array-key, TokenDefinition> $definitions
     * @throws CompilationFailedException
     */
    private function validateOrFail(array $definitions): void
    {
        /** @var array<non-empty-string, TokenDefinition> $known */
        $known = [];

        foreach ($definitions as $definition) {
            $key = self::createKey($definition);

            if ($key === null) {
                continue;
            }

            $previous = $known[$key] ?? null;

            if ($previous === null) {
                $known[$key] = $definition;

                continue;
            }

            throw new CompilationFailedException($definition, \sprintf(
                'The %s token definition recognizes the same fragment as the %s one declared before it',
                $definition,
                $previous,
            ));
        }
    }

    /**
     * @return non-empty-string|null
     */
    private static function createKey(TokenDefinition $definition): ?string
    {
        return match (true) {
            $definition instanceof ValueTokenDefinition => 'value:' . $definition->value,
            $definition instanceof RegexTokenDefinition => self::createRegexKey($definition->regex),
            default => null,
        };
    }

    /**
     * @param non-empty-string $regex
     * @return non-empty-string
     */
    private static function createRegexKey(string $regex): string
    {
        if (\preg_match(self::PATTERN_LITERAL, $regex) !== 1) {
            return 'regex:' . $regex;
        }

        $result = \preg_replace(self::PATTERN_ESCAPE, '$1', $regex);

        return 'value:' . ($result ?? $regex);
    }
}
