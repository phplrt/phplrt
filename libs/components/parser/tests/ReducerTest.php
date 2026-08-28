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
use Phplrt\Parser\Parser;
use Phplrt\Parser\Tests\Stub\ArithmeticLexer;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser')]
#[Test]
final class ReducerTest extends TestCase
{
    public function testReducesRulesToTokens(): void
    {
        $actual = self::createParser()->parse(StringSource::createFromString('1 + 2 - 3'));

        Assert::same(self::describe($actual), ['T_NUMBER(1)', 'T_NUMBER(2)', 'T_NUMBER(3)']);
    }

    public function testOmitsTokensThatAreNotKept(): void
    {
        $actual = self::createParser()->parse(StringSource::createFromString('1 + 2'));

        Assert::count(self::describe($actual), 2);
    }

    public function testReducerOfTerminalReceivesToken(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static function (Context $context, mixed $children): int {
                Assert::instanceOf($children, TokenInterface::class);

                return (int) $children->value;
            },
        ]);

        Assert::same($parser->parse(StringSource::createFromString('1 + 2 + 3')), [1, 2, 3]);
    }

    public function testReducerOfConcatenationReceivesList(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_EXPRESSION => static function (Context $context, mixed $children): int {
                Assert::array($children)->isList();

                return \array_sum($children);
            },
        ]);

        Assert::same($parser->parse(StringSource::createFromString('1 + 2 + 3')), 6);
    }

    public function testReducerOfAlternationReceivesBranch(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_OPERATOR => static function (Context $context, mixed $children): string {
                Assert::same($children, []);

                return '?';
            },
        ]);

        Assert::same($parser->parse(StringSource::createFromString('1 + 2')), [1, '?', 2]);
    }

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

        $parser->parse(StringSource::createFromString('1'));

        Assert::same($received, [[]]);
    }

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

        Assert::same(self::createParser($reducers)->parse(StringSource::createFromString('-1')), ['sign', 1]);
        Assert::same(self::createParser($reducers)->parse(StringSource::createFromString('1')), [1]);

        Assert::same($received, ['sign', []]);
    }

    public function testReducerReturningNullKeepsChildren(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $context, mixed $children): int => (int) $children->value,
            self::RULE_EXPRESSION => static fn(Context $context, mixed $children): mixed => null,
        ]);

        Assert::same($parser->parse(StringSource::createFromString('1 + 2')), [1, 2]);
    }

    public function testContext(): void
    {
        $contexts = [];

        $parser = self::createParser([
            self::RULE_EXPRESSION => static function (Context $context, mixed $children) use (&$contexts): mixed {
                $contexts[] = $context;

                return $children;
            },
        ]);

        $parser->parse(StringSource::createFromString('1 + 2'));

        Assert::count($contexts, 1);
        Assert::same($contexts[0]->rule, self::RULE_EXPRESSION);
        Assert::same($contexts[0]->source->content, '1 + 2');
        Assert::same($contexts[0]->begin, 0);
        Assert::same($contexts[0]->length, 5);
    }

    public function testContextPosition(): void
    {
        $positions = [];

        $parser = self::createParser([
            self::RULE_NUMBER => static function (Context $context, mixed $children) use (&$positions): mixed {
                $positions[] = [$context->begin, $context->length];

                return $children;
            },
        ]);

        $parser->parse(StringSource::createFromString('1 + 22'));

        Assert::same($positions, [[0, 1], [4, 2]]);
    }

    public function testContextPositionOfSequence(): void
    {
        $positions = [];

        $parser = self::createParser([
            self::RULE_EXPRESSION => static function (Context $context, mixed $children) use (&$positions): mixed {
                $positions[] = [$context->begin, $context->length];

                return $children;
            },
        ]);

        $parser->parse(StringSource::createFromString('1 + 22 - 3'));

        Assert::same($positions, [[0, 10]]);
    }

    public function testContextPositionOmitsTokensThatAreNotKept(): void
    {
        $positions = [];

        $parser = self::createParser([
            self::RULE_EXPRESSION => static function (Context $context, mixed $children) use (&$positions): mixed {
                $positions[] = [$context->begin, $context->length];

                return $children;
            },
        ]);

        $parser->parse(StringSource::createFromString('-1'));

        Assert::same($positions, [[1, 1]]);
    }

    public function testContextPositionOfEmptyRule(): void
    {
        $positions = [];

        $parser = self::createParser([
            self::RULE_TAIL => static function (Context $context, mixed $children) use (&$positions): mixed {
                $positions[] = [$context->begin, $context->length];

                return $children;
            },
        ]);

        $parser->parse(StringSource::createFromString('42'));

        Assert::same($positions, [[2, 0]]);
    }

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
            reducers: $reducers,
        );

        Assert::same($parser->parse(StringSource::createFromString('-1 + 2 - 3')), self::createParser($reducers)->parse(StringSource::createFromString('-1 + 2 - 3')));
    }

    public function testReducesEmptyResult(): void
    {
        $grammar = [new Lexeme(ArithmeticLexer::T_NUMBER, false)];

        $analysis = self::analyze($grammar, 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            lookahead: $analysis->lookahead,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
        );

        Assert::same($parser->parse(StringSource::createFromString('1')), []);
    }

    private const int RULE_EXPRESSION = 0;

    private const int RULE_NUMBER = 1;

    private const int RULE_TAIL = 2;

    private const int RULE_SIGN = 4;

    private const int RULE_OPERATOR = 5;

    private static function createGrammar(): array
    {
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

    private static function createParser(array $reducers = []): Parser
    {
        $analysis = self::analyze(self::createGrammar(), self::RULE_EXPRESSION, $reducers);

        return new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            lookahead: $analysis->lookahead,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
            reducers: $reducers,
        );
    }

    private static function describe(mixed $result): array
    {
        Assert::array($result)->isList();

        $tokens = [];

        foreach ($result as $token) {
            Assert::instanceOf($token, TokenInterface::class);

            $tokens[] = ArithmeticLexer::describe($token);
        }

        return $tokens;
    }
}
