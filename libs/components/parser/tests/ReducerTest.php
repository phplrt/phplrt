<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Tests\Stub\ArithmeticLexer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser')]
final class ReducerTest extends TestCase
{
    #[TestDox('The rules without a reducer are reduced to the recognized tokens')]
    public function testReducesRulesToTokens(): void
    {
        $actual = self::createParser()->parse('1 + 2 - 3');

        self::assertSame(
            ['T_NUMBER(1)', 'T_NUMBER(2)', 'T_NUMBER(3)'],
            self::describe($actual),
        );
    }

    #[TestDox('The tokens of the rules that are not kept are omitted')]
    public function testOmitsTokensThatAreNotKept(): void
    {
        // The "+" and "-" lexemes of the grammar are not kept
        $actual = self::createParser()->parse('1 + 2');

        self::assertCount(2, self::describe($actual));
    }

    #[TestDox('The reducer of a terminal receives the token itself')]
    public function testReducerOfTerminalReceivesToken(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static function (Context $context, mixed $children): int {
                self::assertInstanceOf(TokenInterface::class, $children);

                return (int) $children->value;
            },
        ]);

        self::assertSame([1, 2, 3], $parser->parse('1 + 2 + 3'));
    }

    #[TestDox('The reducer of a concatenation receives the list of its children')]
    public function testReducerOfConcatenationReceivesList(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_EXPRESSION => static function (Context $context, mixed $children): int {
                self::assertIsList($children);

                return \array_sum($children);
            },
        ]);

        self::assertSame(6, $parser->parse('1 + 2 + 3'));
    }

    #[TestDox('The reducer of an alternation receives the value of the matched branch')]
    public function testReducerOfAlternationReceivesBranch(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_OPERATOR => static function (Context $context, mixed $children): string {
                self::assertSame([], $children);

                return '?';
            },
        ]);

        self::assertSame([1, '?', 2], $parser->parse('1 + 2'));
    }

    #[TestDox('The reducer of a repetition without iterations receives an empty list')]
    public function testReducerOfRepetitionWithoutIterations(): void
    {
        $received = [];

        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_TAIL => static function (Context $context, mixed $children) use (&$received): mixed {
                $received[] = $children;

                return $children;
            },
        ]);

        $parser->parse('1');

        self::assertSame([[]], $received);
    }

    #[TestDox('The reducer of an optional rule receives its value or an empty list')]
    public function testReducerOfOptionalRule(): void
    {
        $received = [];

        $reducers = [
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_OPERATOR => static fn(Context $context, mixed $children): string => 'sign',
            self::RULE_SIGN => static function (Context $context, mixed $children) use (&$received): mixed {
                $received[] = $children;

                return $children;
            },
        ];

        self::assertSame(['sign', 1], self::createParser($reducers)->parse('-1'));
        self::assertSame([1], self::createParser($reducers)->parse('1'));

        self::assertSame(['sign', []], $received);
    }

    #[TestDox('The reducer returning "null" keeps the children of the rule')]
    public function testReducerReturningNullKeepsChildren(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_EXPRESSION => static fn(Context $context, mixed $children): mixed => null,
        ]);

        self::assertSame([1, 2], $parser->parse('1 + 2'));
    }

    #[TestDox('The context contains the rule, the source and the last recognized token')]
    public function testContext(): void
    {
        $contexts = [];

        $parser = self::createParser([
            self::RULE_EXPRESSION => static function (Context $context, mixed $children) use (&$contexts): mixed {
                $contexts[] = $context;

                return $children;
            },
        ]);

        $parser->parse('1 + 2');

        self::assertCount(1, $contexts);
        self::assertSame(self::RULE_EXPRESSION, $contexts[0]->rule);
        self::assertSame('1 + 2', $contexts[0]->source);
        self::assertSame('2', $contexts[0]->token?->value);
    }

    #[TestDox('The result does not depend on the optional tables')]
    public function testReducesWithoutOptionalTables(): void
    {
        $reducers = [
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_OPERATOR => static fn(Context $context, mixed $children): string => '?',
        ];

        $grammar = self::createGrammar();

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $grammar,
            initial: self::RULE_EXPRESSION,
            merged: self::analyze($grammar, self::RULE_EXPRESSION, $reducers)->merged,
            reducers: $reducers,
        );

        self::assertSame(
            self::createParser($reducers)->parse('-1 + 2 - 3'),
            $parser->parse('-1 + 2 - 3'),
        );
    }

    #[TestDox('The result of an empty grammar rule is an empty list')]
    public function testReducesEmptyResult(): void
    {
        $grammar = [new Lexeme(ArithmeticLexer::T_NUMBER, false)];

        $analysis = self::analyze($grammar, 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            first: $analysis->first,
            nullable: $analysis->nullable,
            kept: $analysis->kept,
            merged: $analysis->merged,
        );

        self::assertSame([], $parser->parse('1'));
    }

    private const int RULE_EXPRESSION = 0;

    private const int RULE_NUMBER = 1;

    private const int RULE_TAIL = 2;

    private const int RULE_SIGN = 4;

    private const int RULE_OPERATOR = 5;

    /**
     * Expression := ("+" | "-")? Number (("+" | "-") Number)*
     *
     * @return list<RuleInterface>
     */
    private static function createGrammar(): array
    {
        /** @var list<RuleInterface> */
        return [
            self::RULE_EXPRESSION => new Concatenation([self::RULE_SIGN, self::RULE_NUMBER, self::RULE_TAIL]),
            self::RULE_NUMBER => new Lexeme(ArithmeticLexer::T_NUMBER),
            self::RULE_TAIL => new Repetition(3),
            3 => new Concatenation([self::RULE_OPERATOR, self::RULE_NUMBER]),
            self::RULE_SIGN => new Optional(self::RULE_OPERATOR),
            self::RULE_OPERATOR => new Alternation([6, 7]),
            6 => new Lexeme(ArithmeticLexer::T_PLUS, false),
            7 => new Lexeme(ArithmeticLexer::T_MINUS, false),
        ];
    }

    /**
     * @param array<int<0, max>, callable(Context, mixed): mixed> $reducers
     */
    private static function createParser(array $reducers = []): Parser
    {
        $analysis = self::analyze(self::createGrammar(), self::RULE_EXPRESSION, $reducers);

        return new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            first: $analysis->first,
            nullable: $analysis->nullable,
            kept: $analysis->kept,
            merged: $analysis->merged,
            reducers: $analysis->reducers,
        );
    }

    /**
     * @return list<string>
     */
    private static function describe(mixed $result): array
    {
        self::assertIsList($result);

        $tokens = [];

        foreach ($result as $token) {
            self::assertInstanceOf(TokenInterface::class, $token);

            $tokens[] = ArithmeticLexer::describe($token);
        }

        return $tokens;
    }
}
