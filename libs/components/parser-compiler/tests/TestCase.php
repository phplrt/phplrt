<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Tests;

use Phplrt\Compiler\Lexer\LexerBuilder;
use Phplrt\Compiler\Parser\ParserBuilder;
use Phplrt\Compiler\Parser\ParserBuilderResult;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Parser;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Reads the arithmetic expressions, like "1 + 2 - 3".
     */
    protected static function createLexerBuilder(): LexerBuilder
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('\s++', 'T_WHITESPACE')->hide();
        $lexer->addPattern('\d++', 'T_NUMBER');
        $lexer->addValue('+', 'T_PLUS');
        $lexer->addValue('-', 'T_MINUS');

        return $lexer;
    }

    protected static function createLexer(LexerBuilder $builder): LexerInterface
    {
        $pathname = __DIR__ . \sprintf('/temp/phplrt-lexer-%s.php', \bin2hex(\random_bytes(8)));

        \file_put_contents($pathname, (string) $builder->generate());

        try {
            /** @var LexerInterface */
            return require $pathname;
        } finally {
            @\unlink($pathname);
        }
    }

    protected static function createParser(LexerInterface $lexer, ParserBuilderResult $result): Parser
    {
        return new Parser(
            lexer: $lexer,
            grammar: $result->grammar,
            initial: $result->initial,
            first: $result->first,
            nullable: $result->nullable,
            kept: $result->kept,
            reducers: $result->createReducerCallbacks(),
        );
    }

    /**
     * Returns the values of every token of the result, no matter how deep it
     * is nested.
     *
     * @return list<string>
     */
    protected static function collectValues(mixed $value): array
    {
        if ($value instanceof TokenInterface) {
            return [$value->value];
        }

        if (!\is_iterable($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $child) {
            foreach (self::collectValues($child) as $inner) {
                $result[] = $inner;
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    protected static function describe(ParserBuilderResult $result): array
    {
        $output = [];

        foreach ($result->grammar as $id => $rule) {
            $output[] = \sprintf('%d: %s', $id, self::describeRule($rule));
        }

        return $output;
    }

    protected static function describeRule(RuleInterface $rule): string
    {
        return match (true) {
            $rule instanceof Lexeme => \sprintf('Lexeme(%d, %s)', $rule->tokenId, $rule->keep ? 'keep' : 'skip'),
            $rule instanceof Concatenation => 'Concatenation(' . \implode(', ', $rule->rules) . ')',
            $rule instanceof Alternation => 'Alternation(' . \implode(', ', $rule->ruleIds) . ')',
            $rule instanceof Optional => \sprintf('Optional(%d)', $rule->ruleId),
            $rule instanceof Repetition => \sprintf('Repetition(%d, %d, %s)', $rule->ruleId, $rule->min, $rule->max),
            default => $rule::class,
        };
    }

    /**
     * @param non-empty-string $name
     */
    protected static function findRule(ParserBuilder $builder, string $name): mixed
    {
        foreach ($builder->rules as $rule) {
            if ($rule->name === $name) {
                return $rule;
            }
        }

        self::fail(\sprintf('Rule "%s" has not been defined', $name));
    }
}
