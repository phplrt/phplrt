<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Tests\Stub\ArithmeticLexer;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser')]
#[Test]
final class MessageTest extends TestCase
{
    public function testMessageOfARuleReplacesTheDefaultOne(): void
    {
        $error = self::readWith([self::RULE_NUMBER => 'a number is expected'], '1 + ');

        Assert::same($error->getMessage(), 'a number is expected');
    }

    public function testDefaultMessageIsReportedWithoutAMessageTable(): void
    {
        $error = self::readWith([], '1 + ');

        Assert::same($error->getMessage(), 'Syntax error, unexpected end of input, T_NUMBER expected');
    }

    public function testMessageOfARuleThatIsNeverEnteredIsNotReported(): void
    {
        $error = self::readWith([self::RULE_OPERATOR => 'an operator is expected'], '1 + ');

        Assert::same($error->getMessage(), 'Syntax error, unexpected end of input, T_NUMBER expected');
    }

    public function testMessageOfARuleRejectedByItsFirstTokenIsNotReported(): void
    {
        $error = self::readWith([self::RULE_NUMBER => 'a number is expected'], '+ 1');

        Assert::same($error->getMessage(), 'Syntax error, unexpected "+" (T_PLUS), T_NUMBER expected');
    }

    public function testMessageOfTheInitialRuleIsReportedWhenTheSourceIsNotReadToItsEnd(): void
    {
        $error = self::readWith([self::RULE_EXPRESSION => 'an expression is expected'], '1 2');

        Assert::same($error->getMessage(), 'an expression is expected');
    }

    public function testMessageOfTheInitialRuleGivesWayToAMessageWrittenInsideIt(): void
    {
        $error = self::readWith([
            self::RULE_EXPRESSION => 'an expression is expected',
            self::RULE_NUMBER => 'a number is expected',
        ], '1 + ');

        Assert::same($error->getMessage(), 'a number is expected');
    }

    public function testMessageOfTheDeepestFailureIsReported(): void
    {
        $error = self::readWith([
            self::RULE_NUMBER => 'a number is expected',
            self::RULE_OPERATOR => 'an operator is expected',
        ], '1 + ');

        Assert::same($error->getMessage(), 'a number is expected');
    }

    public function testCodeIsTheRuleTheMessageIsWrittenOnCountedFromOne(): void
    {
        $error = self::readWith([self::RULE_NUMBER => 'a number is expected'], '1 + ');

        Assert::same($error->getCode(), self::RULE_NUMBER + 1);
    }

    public function testCodeOfTheFirstRuleTellsTheDefaultMessageApart(): void
    {
        $error = self::readWith([self::RULE_EXPRESSION => 'an expression is expected'], '1 2');

        Assert::same($error->getCode(), 1);
    }

    public function testCodeIsNotReportedWithoutAMessage(): void
    {
        $error = self::readWith([], '1 + ');

        Assert::same($error->getCode(), 0);
    }

    public function testExpectedTokensAreStillReported(): void
    {
        $error = self::readWith([self::RULE_NUMBER => 'a number is expected'], '1 + ');

        Assert::same($error->expected, ['T_NUMBER']);
    }

    #[DataSet(['{token}', 'end of input'], 'token')]
    #[DataSet(['{name}', 'EndOfInput'], 'name')]
    #[DataSet(['[{value}]', '[]'], 'value')]
    #[DataSet(['{offset}', '4'], 'offset')]
    #[DataSet(['{line}', '1'], 'line')]
    #[DataSet(['{column}', '5'], 'column')]
    #[DataSet(['{expected}', 'T_NUMBER'], 'expected')]
    public function testPlaceholderIsFilledIn(string $message, string $expected): void
    {
        $error = self::readWith([self::RULE_NUMBER => $message], '1 + ');

        Assert::same($error->getMessage(), $expected);
    }

    public function testPlaceholderIsFoundAnywhereInTheMessage(): void
    {
        $error = self::readWith([self::RULE_NUMBER => 'got {name} at {line}:{column}'], '1 + ');

        Assert::same($error->getMessage(), 'got EndOfInput at 1:5');
    }

    public function testLineAndColumnAreCountedFromTheSource(): void
    {
        $error = self::readWith([self::RULE_NUMBER => '{line}:{column}'], "1 +\n  + ");

        Assert::same($error->getMessage(), '2:3');
    }

    #[DataSet(['{{name}}', '{name}'], 'placeholder')]
    #[DataSet(['{{', '{'], 'opening brace')]
    #[DataSet(['}}', '}'], 'closing brace')]
    #[DataSet(['{{{name}}}', '{EndOfInput}'], 'both')]
    public function testDoubledBraceStandsForABraceOfTheMessage(string $message, string $expected): void
    {
        $error = self::readWith([self::RULE_NUMBER => $message], '1 + ');

        Assert::same($error->getMessage(), $expected);
    }

    public function testUnknownPlaceholderIsLeftAsItIsWritten(): void
    {
        $error = self::readWith([self::RULE_NUMBER => 'a {something} is expected'], '1 + ');

        Assert::same($error->getMessage(), 'a {something} is expected');
    }

    public function testMessageDoesNotChangeRecognition(): void
    {
        $source = StringSource::createFromString('1 + 2 + 3');

        $labelled = self::createParser([self::RULE_NUMBER => 'a number is expected'])
            ->analyze($source, Mode::SyntaxCheck);

        Assert::instanceOf($labelled, SuccessfulResult::class);
    }

    private const int RULE_EXPRESSION = 0;

    private const int RULE_NUMBER = 1;

    private const int RULE_OPERATOR = 4;

    /**
     * @param array<int, non-empty-string> $messages
     */
    private static function readWith(array $messages, string $source): UnexpectedTokenException
    {
        try {
            self::createParser($messages)
                ->parse(StringSource::createFromString($source));
        } catch (UnexpectedTokenException $e) {
            return $e;
        }

        Assert::fail('The source is expected to break the reading');
    }

    /**
     * @param array<int, non-empty-string> $messages
     */
    private static function createParser(array $messages = []): Parser
    {
        $grammar = [
            self::RULE_EXPRESSION => new Concatenation([self::RULE_NUMBER, 2]),
            self::RULE_NUMBER => new Lexeme(ArithmeticLexer::T_NUMBER),
            2 => new Repetition(3),
            3 => new Concatenation([self::RULE_OPERATOR, self::RULE_NUMBER]),
            self::RULE_OPERATOR => new Alternation([5, 6]),
            5 => new Lexeme(ArithmeticLexer::T_PLUS, false),
            6 => new Lexeme(ArithmeticLexer::T_MINUS, false),
        ];

        $analysis = self::analyze($grammar, self::RULE_EXPRESSION);

        return new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            lookahead: $analysis->lookahead,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
            expectations: [
                ArithmeticLexer::T_NUMBER => 'T_NUMBER',
                ArithmeticLexer::T_PLUS => 'T_PLUS',
                ArithmeticLexer::T_MINUS => 'T_MINUS',
            ],
            messages: $messages,
        );
    }
}
