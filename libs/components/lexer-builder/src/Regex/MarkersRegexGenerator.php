<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Regex;

use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\ValueTokenDefinition;
use Phplrt\Lexer\Builder\Exception\InvalidArgumentException;
use Phplrt\Lexer\Builder\Internal\PatternFormatter;

/**
 * @readonly
 */
final class MarkersRegexGenerator extends RegexGenerator
{
    private const PATTERN_TOKEN = '(?:(?:%s)(*MARK:%s))';
    private const PATTERN_BODY = '\\G(?|%s)';

    public function generate(array $tokens, array $flags): string
    {
        return $this->formatFullRegex(
            regex: $this->formatRegex($tokens, $flags),
            flags: $flags,
        );
    }

    /**
     * @param array<int, TokenDefinition> $tokens
     * @param list<RegexModifier> $flags
     * @return non-empty-string
     * @throws InvalidArgumentException
     */
    private function formatRegex(array $tokens, array $flags): string
    {
        $chunks = [];

        foreach ($tokens as $id => $definition) {
            $chunks[] = $this->formatToken($definition, $id, $flags);
        }

        return \sprintf(self::PATTERN_BODY, \implode('|', $chunks));
    }

    /**
     * @param list<RegexModifier> $flags
     * @return non-empty-string
     * @throws InvalidArgumentException
     */
    private function formatToken(TokenDefinition $token, int $id, array $flags): string
    {
        return match (true) {
            $token instanceof RegexTokenDefinition => \vsprintf(self::PATTERN_TOKEN, [
                PatternFormatter::formatExpression($token->regex, $flags),
                $this->escapeValue((string) $id),
            ]),
            $token instanceof ValueTokenDefinition => \vsprintf(self::PATTERN_TOKEN, [
                $this->escapeValue($token->value),
                $this->escapeValue((string) $id),
            ]),
            default => throw new InvalidArgumentException(\sprintf(
                'Unsupported %s token definition',
                $token::class,
            )),
        };
    }
}
