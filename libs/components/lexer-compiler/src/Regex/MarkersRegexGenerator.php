<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Regex;

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
        return new RegexGeneratorResult(
            pattern: $this->formatFullRegex(
                regex: $this->formatRegex($tokens),
                flags: $flags,
            ),
            tokens: $tokens,
        );
    }

    /**
     * @param list<TokenDefinition> $tokens
     *
     * @return non-empty-string
     * @throws InvalidArgumentException
     */
    private function formatRegex(array $tokens): string
    {
        $chunks = [];

        foreach ($tokens as $id => $definition) {
            $chunks[] = $this->formatToken($definition, $id);
        }

        return \sprintf(self::PATTERN_BODY, \implode('|', $chunks));
    }

    /**
     * @return non-empty-string
     * @throws InvalidArgumentException
     */
    private function formatToken(TokenDefinition $token, int $id): string
    {
        return match (true) {
            $token instanceof RegexTokenDefinition => \vsprintf(self::PATTERN_TOKEN, [
                $this->escapePattern($token->regex),
                $this->escapeValue((string) $id),
            ]),
            $token instanceof ValueTokenDefinition => \vsprintf(self::PATTERN_TOKEN, [
                $this->escapeValue($token->value),
                $this->escapeValue((string) $id),
            ]),
            default => throw new InvalidArgumentException(\sprintf(
                'Unsupported %s token definition',
                /** @phpstan-ignore-next-line : PHPStan false-positive */
                $token::class,
            )),
        };
    }
}
