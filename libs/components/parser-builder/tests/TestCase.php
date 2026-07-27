<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Builder\ParserBuilderResult;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
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
        return new RuntimeLexerTransformer()
            ->transform($builder->build());
    }

    protected static function createParser(LexerInterface $lexer, ParserBuilderResult $result): ParserInterface
    {
        return $result->toParser($lexer);
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
            $rule instanceof Concatenation => 'Concatenation(' . \implode(', ', $rule->ruleIds) . ')',
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
