<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Regex;

use Phplrt\Compiler\Lexer\Definition\AliasedDefinition;
use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Definition\ValueTokenDefinition;
use Phplrt\Compiler\Lexer\Exception\InvalidArgumentException;

/**
 * @template-extends RegexGenerator<RegexGeneratorResult>
 */
final readonly class MarkersRegexGenerator extends RegexGenerator
{
    private const string PATTERN_TOKEN = '(?:(?:%s)(*MARK:%s))';
    private const string PATTERN_BODY = '\\G(?|%s)';

    public function generate(array $tokens, array $flags): RegexGeneratorResult
    {
        $aliases = $this->getAliasedDefinitions($tokens);

        return new RegexGeneratorResult(
            pattern: $this->formatFullRegex(
                regex: $this->formatRegex($aliases),
                flags: $flags,
            ),
            tokens: $aliases,
        );
    }

    /**
     * @param list<AliasedDefinition> $aliases
     *
     * @return non-empty-string
     * @throws InvalidArgumentException
     */
    private function formatRegex(array $aliases): string
    {
        $chunks = [];

        foreach ($aliases as $definition) {
            $chunks[] = $this->formatToken(
                token: $definition->definition,
                alias: $definition->alias,
            );
        }

        return \sprintf(self::PATTERN_BODY, \implode('|', $chunks));
    }

    /**
     * @param non-empty-string $alias
     *
     * @return non-empty-string
     * @throws InvalidArgumentException
     */
    private function formatToken(TokenDefinition $token, string $alias): string
    {
        return match (true) {
            $token instanceof RegexTokenDefinition => \vsprintf(self::PATTERN_TOKEN, [
                $this->escapePattern($token->regex),
                $this->escapeValue($alias),
            ]),
            $token instanceof ValueTokenDefinition => \vsprintf(self::PATTERN_TOKEN, [
                $this->escapeValue($token->value),
                $this->escapeValue($alias),
            ]),
            default => throw new InvalidArgumentException(\sprintf(
                'Unsupported %s token definition',
                $token::class,
            )),
        };
    }
}
