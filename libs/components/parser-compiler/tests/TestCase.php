<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Tests;

use Phplrt\Compiler\Lexer\LexerBuilder;
use Phplrt\Compiler\Parser\ParserBuilder;
use Phplrt\Compiler\Parser\ParserBuilderResult;
use Phplrt\Contracts\Lexer\LexerInterface;
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
        $lexer->match('\s++', 'T_WHITESPACE')->hide();
        $lexer->match('\d++', 'T_NUMBER');
        $lexer->value('+', 'T_PLUS');
        $lexer->value('-', 'T_MINUS');

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
            reducers: $result->reducers,
        );
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
