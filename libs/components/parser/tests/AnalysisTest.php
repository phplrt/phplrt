<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\FailureResult;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Context;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Predicate;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Tests\Stub\ArithmeticLexer;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Assert\ExpectNoAssertions;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser')]
#[Test]
final class AnalysisTest extends TestCase
{
    public function testCompleteSourceIsSuccessful(): void
    {
        $actual = self::createParser()->analyze(StringSource::createFromString('1 + 2 + 3'));

        Assert::instanceOf($actual, SuccessfulResult::class);
    }

    public function testIncompleteSourceIsPartial(): void
    {
        $actual = self::createParser()->analyze(StringSource::createFromString('1 + 2 +'));

        Assert::instanceOf($actual, PartialResult::class);
        Assert::same($actual->token->name, 'T_PLUS');
        Assert::same($actual->token->offset, 6);
    }

    public function testUnreadableSourceIsFailure(): void
    {
        $actual = self::createParser()->analyze(StringSource::createFromString('+ 1'));

        Assert::instanceOf($actual, FailureResult::class);
        Assert::same($actual->token->name, 'T_PLUS');
    }

    public function testSourceThatCannotBeRewoundIsRead(): void
    {
        $stream = self::createNonSeekableResource('1 + 2 + 3');

        try {
            $actual = self::createParser()->analyze(new ResourceSource($stream));

            Assert::instanceOf($actual, SuccessfulResult::class);
        } finally {
            \fclose($stream);
        }
    }

    #[ExpectNoAssertions]
    #[DataSet([''], 'empty')]
    #[DataSet(['+'], 'operator only')]
    #[DataSet(['1 + 2 +'], 'partial')]
    #[DataSet(['1 + 2 + 3'], 'complete')]
    public function testAnalysisNeverThrows(string $source): void
    {
        self::createParser()->analyze(StringSource::createFromString($source));
    }

    public function testFullModeBuildsTheValue(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $ctx, mixed $children): int => (int) $children->value,
        ]);

        $complete = $parser->analyze(StringSource::createFromString('1 + 2 + 3'), Mode::Tolerant);
        $partial = $parser->analyze(StringSource::createFromString('1 + 2 + 3 -'), Mode::Tolerant);

        Assert::instanceOf($complete, SuccessfulResult::class);
        Assert::instanceOf($partial, PartialResult::class);

        Assert::same($complete->value, [1, 2, 3]);
        Assert::same($partial->value, [1, 2, 3], 'The fragment is built the same way');
    }

    public function testFastModeBuildsNothing(): void
    {
        $reduced = false;

        $parser = self::createParser([
            self::RULE_NUMBER => static function (Context $ctx, mixed $children) use (&$reduced): int {
                $reduced = true;

                return (int) $children->value;
            },
        ]);

        $actual = $parser->analyze(StringSource::createFromString('1 + 2 + 3'), Mode::SyntaxCheck);

        Assert::instanceOf($actual, SuccessfulResult::class);
        Assert::null($actual->value);
        Assert::false($reduced, 'A fast analysis runs no reducer');
    }

    #[DataSet(['1 + 2 + 3'], 'complete')]
    #[DataSet(['1 + 2 +'], 'partial')]
    #[DataSet(['+ 1'], 'unexpected')]
    #[DataSet([''], 'empty')]
    public function testModeDoesNotChangeRecognition(string $source): void
    {
        $parser = self::createParser();

        Assert::same(
            $parser->analyze(StringSource::createFromString($source), Mode::SyntaxCheck)::class,
            $parser->analyze(StringSource::createFromString($source), Mode::Tolerant)::class,
        );
    }

    public function testFailureReportsExpectedTokens(): void
    {
        $actual = self::createParser()->analyze(StringSource::createFromString('+ 1'), Mode::SyntaxCheck);

        Assert::instanceOf($actual, FailureResult::class);

        $error = $actual->error;

        Assert::same($error->getMessage(), 'Syntax error, unexpected "+" (T_PLUS), T_NUMBER expected');
    }

    public function testPartialReportsWhereTheReadingBroke(): void
    {
        $actual = self::createParser()->analyze(StringSource::createFromString('1 + 2 +'), Mode::SyntaxCheck);

        Assert::instanceOf($actual, PartialResult::class);

        Assert::same($actual->token->name, 'T_PLUS');
        Assert::same($actual->token->offset, 6);

        $error = $actual->error;

        Assert::same($error->token->channel, Channel::EndOfInput);
        Assert::same($error->getMessage(), 'Syntax error, unexpected end of input, T_NUMBER expected');
    }

    #[DataSet(['1 + 2 +'], 'partial')]
    #[DataSet(['1 1'], 'consecutive numbers')]
    #[DataSet(['+ 1'], 'leading operator')]
    #[DataSet([''], 'empty')]
    public function testAnalysisReportsTheErrorOfAnOrdinaryReading(string $source): void
    {
        $result = self::createParser()->analyze(StringSource::createFromString($source), Mode::SyntaxCheck);

        Assert::notSame($result::class, SuccessfulResult::class);

        try {
            self::createParser()->parse(StringSource::createFromString($source));

            Assert::fail('The source is expected to be rejected');
        } catch (UnexpectedTokenException $e) {
            $error = $result->error;

            Assert::same($error::class, $e::class);
            Assert::same($error->getMessage(), $e->getMessage());
            Assert::same($error->token->offset, $e->token->offset);

            Assert::same(self::describeError($error), self::describeError($e), 'Both print the very same report');
        }
    }

    private static function describeError(UnexpectedTokenException $error): string
    {
        return \explode("\n#0 ", (string) $error)[0];
    }

    public function testTheReportedErrorIsThrowable(): void
    {
        $result = self::createParser()->analyze(StringSource::createFromString('+ 1'), Mode::SyntaxCheck);

        Assert::instanceOf($result, FailureResult::class);

        Expect::exception(UnexpectedTokenException::class)
        ->withMessage('Syntax error, unexpected "+" (T_PLUS), T_NUMBER expected');

        throw $result->error;
    }

    public function testExpectedTokensWithoutStartPrediction(): void
    {
        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: self::createGrammar(),
            initial: self::RULE_EXPRESSION,
            expectations: self::createExpectations(),
        );

        $actual = $parser->analyze(StringSource::createFromString('+ 1'), Mode::SyntaxCheck);

        Assert::instanceOf($actual, FailureResult::class);
    }

    public function testExpectedTokensOfAParserThatCannotNameThem(): void
    {
        $analysis = self::analyze(self::createGrammar(), self::RULE_EXPRESSION);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            startPrediction: $analysis->startPrediction,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
        );

        $actual = $parser->analyze(StringSource::createFromString('+ 1'), Mode::SyntaxCheck);

        Assert::instanceOf($actual, FailureResult::class);

        Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "+" (T_PLUS)');
    }

    public function testExpectedTokensOfSiblingRules(): void
    {
        $grammar = [
            0 => new Alternation([1, 2]),
            1 => new Lexeme(ArithmeticLexer::T_PLUS),
            2 => new Lexeme(ArithmeticLexer::T_MINUS),
        ];

        $analysis = self::analyze($grammar, 0);

        $withTables = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            startPrediction: $analysis->startPrediction,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
            expectations: self::createExpectations(),
        );

        $withoutTables = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $grammar,
            initial: 0,
            expectations: self::createExpectations(),
        );

        $expected = 'Syntax error, unexpected "1" (T_NUMBER), T_PLUS or T_MINUS expected';

        foreach (['with' => $withTables, 'without' => $withoutTables] as $name => $parser) {
            $actual = $parser->analyze(StringSource::createFromString('1'), Mode::SyntaxCheck);

            Assert::instanceOf($actual, FailureResult::class);
            Assert::same($actual->error->getMessage(), $expected, \sprintf('Both branches are expected to be told %s the start prediction', $name));
        }
    }

    public function testExpectedTokensOfAlternativesNeverEntered(): void
    {
        $grammar = [
            0 => new Alternation([1, 2]),
            1 => new Concatenation([3]),
            2 => new Concatenation([4]),
            3 => new Lexeme(ArithmeticLexer::T_PLUS),
            4 => new Lexeme(ArithmeticLexer::T_MINUS),
        ];

        $analysis = self::analyze($grammar, 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            startPrediction: $analysis->startPrediction,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
            expectations: self::createExpectations(),
        );

        $actual = $parser->analyze(StringSource::createFromString('1'), Mode::SyntaxCheck);

        Assert::instanceOf($actual, FailureResult::class);

        Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "1" (T_NUMBER), T_PLUS or T_MINUS expected');
    }

    public function testExpectedTokensOfAFailedNegativePredicate(): void
    {
        $grammar = [
            0 => new Alternation([1, 2]),
            1 => new Concatenation([3, 4]),
            2 => new Lexeme(ArithmeticLexer::T_PLUS),
            3 => new Predicate(4, isExpected: false),
            4 => new Lexeme(ArithmeticLexer::T_MINUS),
        ];

        $analysis = self::analyze($grammar, 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            startPrediction: $analysis->startPrediction,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
            expectations: self::createExpectations(),
        );

        $actual = $parser->analyze(StringSource::createFromString('-'), Mode::SyntaxCheck);

        Assert::instanceOf($actual, FailureResult::class);

        Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "-" (T_MINUS), T_PLUS expected');
    }

    public function testLookingAheadDoesNotMoveTheError(): void
    {
        // &(<T_NUMBER> <T_NUMBER> <T_NUMBER>) <T_NUMBER> <T_PLUS> | <T_NUMBER> <T_MINUS>
        $grammar = [
            0 => new Alternation([1, 2]),
            1 => new Concatenation([3, 5, 6]),
            2 => new Concatenation([5, 7]),
            3 => new Predicate(4),
            4 => new Concatenation([5, 5, 5]),
            5 => new Lexeme(ArithmeticLexer::T_NUMBER),
            6 => new Lexeme(ArithmeticLexer::T_PLUS),
            7 => new Lexeme(ArithmeticLexer::T_MINUS),
        ];

        foreach (self::createParsersOf($grammar, 0) as $name => $parser) {
            $actual = $parser->analyze(StringSource::createFromString('1 2 -'), Mode::SyntaxCheck);

            Assert::instanceOf($actual, FailureResult::class);
            Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "2" (T_NUMBER), T_MINUS expected', $name);
            Assert::same($actual->error->token->offset, 2, $name);
        }
    }

    public function testRuleOfANegativePredicateIsNotExpected(): void
    {
        foreach (self::createParsersOf(self::createNegativePredicateGrammar(), 0) as $name => $parser) {
            $actual = $parser->analyze(StringSource::createFromString('1 1'), Mode::SyntaxCheck);

            Assert::instanceOf($actual, FailureResult::class);
            Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "1" (T_NUMBER), T_MINUS expected', $name);
        }
    }

    public function testNegativePredicateFailsWhereItStands(): void
    {
        foreach (self::createParsersOf(self::createNegativePredicateGrammar(), 0) as $name => $parser) {
            $actual = $parser->analyze(StringSource::createFromString('1 + -'), Mode::SyntaxCheck);

            Assert::instanceOf($actual, FailureResult::class);
            Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "+" (T_PLUS)', $name);
            Assert::same($actual->error->token->offset, 2, $name);
        }
    }

    public function testNegativePredicateIsRead(): void
    {
        foreach (self::createParsersOf(self::createNegativePredicateGrammar(), 0) as $name => $parser) {
            $actual = $parser->analyze(StringSource::createFromString('1 -'), Mode::SyntaxCheck);

            Assert::instanceOf($actual, SuccessfulResult::class, $name);
        }
    }

    public function testPositivePredicateFailingAheadExpectsNothing(): void
    {
        // &(<T_NUMBER> <T_PLUS>) <T_NUMBER> <T_MINUS>
        $grammar = [
            0 => new Concatenation([1, 3, 5]),
            1 => new Predicate(2),
            2 => new Concatenation([3, 4]),
            3 => new Lexeme(ArithmeticLexer::T_NUMBER),
            4 => new Lexeme(ArithmeticLexer::T_PLUS),
            5 => new Lexeme(ArithmeticLexer::T_MINUS),
        ];

        foreach (self::createParsersOf($grammar, 0) as $name => $parser) {
            $actual = $parser->analyze(StringSource::createFromString('1 -'), Mode::SyntaxCheck);

            Assert::instanceOf($actual, FailureResult::class);
            Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "1" (T_NUMBER)', $name);
            Assert::same($actual->error->token->offset, 0, $name);
        }
    }

    public function testPositivePredicateRejectedByItsRuleExpectsIt(): void
    {
        // &(<T_NUMBER> <T_PLUS>) <T_NUMBER> <T_MINUS>
        $grammar = [
            0 => new Concatenation([1, 3, 5]),
            1 => new Predicate(2),
            2 => new Concatenation([3, 4]),
            3 => new Lexeme(ArithmeticLexer::T_NUMBER),
            4 => new Lexeme(ArithmeticLexer::T_PLUS),
            5 => new Lexeme(ArithmeticLexer::T_MINUS),
        ];

        // Note: Without the start prediction the rule of the predicate is not
        //       known to be rejected by the token, so it is looked ahead for
        //       and nothing is said about what has been expected instead.
        $expected = [
            'with tables' => 'Syntax error, unexpected "-" (T_MINUS), T_NUMBER expected',
            'without tables' => 'Syntax error, unexpected "-" (T_MINUS)',
        ];

        foreach (self::createParsersOf($grammar, 0) as $name => $parser) {
            $actual = $parser->analyze(StringSource::createFromString('-'), Mode::SyntaxCheck);

            Assert::instanceOf($actual, FailureResult::class);
            Assert::same($actual->error->getMessage(), $expected[$name], $name);
        }
    }

    public function testNegativePredicateIsReadInPlace(): void
    {
        $analysis = self::analyze(self::createNegativePredicateGrammar(), 0);

        Assert::same($analysis->sequencePrediction[0], [1, -2 - 1, 3]);
    }

    public function testNegativePredicateIsReadWithoutStartPrediction(): void
    {
        $analysis = self::analyze(self::createNegativePredicateGrammar(), 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            expectations: self::createExpectations(),
            sequencePrediction: $analysis->sequencePrediction,
        );

        $actual = $parser->analyze(StringSource::createFromString('1 + -'), Mode::SyntaxCheck);

        Assert::instanceOf($actual, FailureResult::class);
        Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "+" (T_PLUS)');
    }

    public function testRepetitionOfAnEmptyBodyReadsEveryTurn(): void
    {
        // (<T_NUMBER>?){2} <T_MINUS>
        $grammar = [
            0 => new Concatenation([1, 3]),
            1 => new Repetition(2, min: 2, max: 2),
            2 => new Optional(4),
            3 => new Lexeme(ArithmeticLexer::T_MINUS),
            4 => new Lexeme(ArithmeticLexer::T_NUMBER),
        ];

        foreach (self::createParsersOf($grammar, 0) as $name => $parser) {
            foreach (['-', '1 -', '1 1 -'] as $source) {
                $actual = $parser->analyze(StringSource::createFromString($source), Mode::SyntaxCheck);

                Assert::instanceOf($actual, SuccessfulResult::class, "$name: $source");
            }
        }
    }

    public function testNegativePredicateWithReducerIsEntered(): void
    {
        $analysis = self::analyze(self::createNegativePredicateGrammar(), 0, [
            2 => static fn(Context $ctx, mixed $children): string => 'predicate',
        ]);

        Assert::false(isset($analysis->sequencePrediction[0]));
    }

    public function testOptionalOfANegativePredicateIsEntered(): void
    {
        // <T_NUMBER> (!<T_PLUS>)? <T_MINUS>
        $grammar = [
            0 => new Concatenation([1, 2, 3]),
            1 => new Lexeme(ArithmeticLexer::T_NUMBER),
            2 => new Optional(4),
            3 => new Lexeme(ArithmeticLexer::T_MINUS),
            4 => new Predicate(5, isExpected: false),
            5 => new Lexeme(ArithmeticLexer::T_PLUS),
        ];

        Assert::false(isset(self::analyze($grammar, 0)->sequencePrediction[0]));

        foreach (self::createParsersOf($grammar, 0) as $name => $parser) {
            $actual = $parser->analyze(StringSource::createFromString('1 + -'), Mode::SyntaxCheck);

            Assert::instanceOf($actual, FailureResult::class);
            Assert::same($actual->error->getMessage(), 'Syntax error, unexpected "+" (T_PLUS), T_MINUS expected', $name);
        }
    }

    public function testPositivePredicateBeginsWithTheTokensOfItsRule(): void
    {
        $grammar = [
            0 => new Concatenation([1, 2, 3]),
            1 => new Predicate(4),
            2 => new Predicate(5, isExpected: false),
            3 => new Lexeme(ArithmeticLexer::T_NUMBER),
            4 => new Lexeme(ArithmeticLexer::T_NUMBER),
            5 => new Lexeme(ArithmeticLexer::T_PLUS),
        ];

        $analysis = self::analyze($grammar, 0);

        Assert::same($analysis->startPrediction[1], [ArithmeticLexer::T_NUMBER => true]);
        Assert::null($analysis->startPrediction[2]);
    }

    public function testPositivePredicateNarrowsTheSequence(): void
    {
        // &(<T_NUMBER> | <T_PLUS>) (<T_PLUS> | <T_MINUS>)
        $grammar = [
            0 => new Concatenation([1, 2]),
            1 => new Predicate(3),
            2 => new Alternation([5, 6]),
            3 => new Alternation([4, 5]),
            4 => new Lexeme(ArithmeticLexer::T_NUMBER),
            5 => new Lexeme(ArithmeticLexer::T_PLUS),
            6 => new Lexeme(ArithmeticLexer::T_MINUS),
        ];

        $analysis = self::analyze($grammar, 0);

        Assert::same($analysis->startPrediction[0], [ArithmeticLexer::T_PLUS => true]);
    }

    public function testNegativePredicateNarrowsTheSequence(): void
    {
        // !<T_PLUS> (<T_PLUS> | <T_MINUS>)
        $grammar = [
            0 => new Concatenation([1, 2]),
            1 => new Predicate(3, isExpected: false),
            2 => new Alternation([3, 4]),
            3 => new Lexeme(ArithmeticLexer::T_PLUS),
            4 => new Lexeme(ArithmeticLexer::T_MINUS),
        ];

        $analysis = self::analyze($grammar, 0);

        Assert::same($analysis->startPrediction[0], [ArithmeticLexer::T_MINUS => true]);
    }

    public function testNegativePredicateOfASequenceNarrowsNothing(): void
    {
        // !(<T_PLUS> <T_MINUS>) (<T_PLUS> | <T_MINUS>)
        $grammar = [
            0 => new Concatenation([1, 2]),
            1 => new Predicate(5, isExpected: false),
            2 => new Alternation([3, 4]),
            3 => new Lexeme(ArithmeticLexer::T_PLUS),
            4 => new Lexeme(ArithmeticLexer::T_MINUS),
            5 => new Concatenation([3, 4]),
        ];

        $analysis = self::analyze($grammar, 0);

        Assert::same($analysis->startPrediction[0], [
            ArithmeticLexer::T_PLUS => true,
            ArithmeticLexer::T_MINUS => true,
        ]);
    }

    public function testEmptyFragmentIsRead(): void
    {
        $grammar = [
            0 => new Repetition(1),
            1 => new Lexeme(ArithmeticLexer::T_NUMBER),
        ];

        $analysis = self::analyze($grammar, 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            startPrediction: $analysis->startPrediction,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
            expectations: self::createExpectations(),
        );

        $actual = $parser->analyze(StringSource::createFromString('+ 1'));

        Assert::instanceOf($actual, PartialResult::class);
        Assert::same($actual->value, []);
        Assert::same($actual->token->offset, 0, 'Nothing of the source has been read');
    }

    public function testEmptySourceMayBeSuccessful(): void
    {
        $grammar = [
            0 => new Repetition(1),
            1 => new Lexeme(ArithmeticLexer::T_NUMBER),
        ];

        $analysis = self::analyze($grammar, 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            startPrediction: $analysis->startPrediction,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
            expectations: self::createExpectations(),
        );

        $actual = $parser->analyze(StringSource::createEmpty());

        Assert::instanceOf($actual, SuccessfulResult::class);
        Assert::same($actual->value, []);
    }

    private const RULE_EXPRESSION = 0;

    private const RULE_NUMBER = 1;

    private const RULE_OPERATOR = 4;

    private static function createGrammar(): array
    {
        return [
            self::RULE_EXPRESSION => new Concatenation([self::RULE_NUMBER, 2]),
            self::RULE_NUMBER => new Lexeme(ArithmeticLexer::T_NUMBER),
            2 => new Repetition(3),
            3 => new Concatenation([self::RULE_OPERATOR, self::RULE_NUMBER]),
            self::RULE_OPERATOR => new Alternation([5, 6]),
            5 => new Lexeme(ArithmeticLexer::T_PLUS, false),
            6 => new Lexeme(ArithmeticLexer::T_MINUS, false),
        ];
    }

    /**
     * <T_NUMBER> !<T_PLUS> <T_MINUS>
     */
    private static function createNegativePredicateGrammar(): array
    {
        return [
            0 => new Concatenation([1, 2, 3]),
            1 => new Lexeme(ArithmeticLexer::T_NUMBER),
            2 => new Predicate(4, isExpected: false),
            3 => new Lexeme(ArithmeticLexer::T_MINUS),
            4 => new Lexeme(ArithmeticLexer::T_PLUS),
        ];
    }

    /**
     * The very same grammar recognized with the tables the analysis has
     * built and without them.
     *
     * @return array<non-empty-string, Parser>
     */
    private static function createParsersOf(array $grammar, int $initial): array
    {
        $analysis = self::analyze($grammar, $initial);

        return [
            'with tables' => new Parser(
                lexer: new ArithmeticLexer(),
                grammar: $analysis->grammar,
                initial: $analysis->initial,
                startPrediction: $analysis->startPrediction,
                kept: $analysis->kept,
                choicePrediction: $analysis->choicePrediction,
                sequencePrediction: $analysis->sequencePrediction,
                expectations: self::createExpectations(),
            ),
            'without tables' => new Parser(
                lexer: new ArithmeticLexer(),
                grammar: $grammar,
                initial: $initial,
                expectations: self::createExpectations(),
            ),
        ];
    }

    private static function createExpectations(): array
    {
        return [
            ArithmeticLexer::T_NUMBER => 'T_NUMBER',
            ArithmeticLexer::T_PLUS => 'T_PLUS',
            ArithmeticLexer::T_MINUS => 'T_MINUS',
        ];
    }

    private static function createParser(array $reducers = []): Parser
    {
        $analysis = self::analyze(self::createGrammar(), self::RULE_EXPRESSION, $reducers);

        return new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            startPrediction: $analysis->startPrediction,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
            expectations: self::createExpectations(),
            reducers: $reducers,
        );
    }
}
