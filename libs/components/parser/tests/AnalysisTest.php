<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Parser\Analysis\Result\FailureResult;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Context;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Predicate;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Tests\Stub\ArithmeticLexer;
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser')]
final class AnalysisTest extends TestCase
{
    #[TestDox('A source the grammar describes in full is a success')]
    public function testCompleteSourceIsSuccessful(): void
    {
        $actual = self::createParser()->analyze(new Source('1 + 2 + 3'));

        self::assertInstanceOf(SuccessfulResult::class, $actual);
        self::assertSame([], $actual->diagnostics, 'A source read in full has nothing to report');
    }

    #[TestDox('A source the grammar reads only in part is partial')]
    public function testIncompleteSourceIsPartial(): void
    {
        // The trailing operator opens an iteration the grammar cannot finish,
        // so the whole iteration is given back
        $actual = self::createParser()->analyze(new Source('1 + 2 +'));

        self::assertInstanceOf(PartialResult::class, $actual);
        self::assertSame('T_PLUS', $actual->token->name);
        self::assertSame(6, $actual->token->offset);
    }

    #[TestDox('A source the grammar cannot begin to read is a failure')]
    public function testUnreadableSourceIsFailure(): void
    {
        $actual = self::createParser()->analyze(new Source('+ 1'));

        self::assertInstanceOf(FailureResult::class, $actual);
        self::assertSame('T_PLUS', $actual->token->name);
    }

    #[TestDox('Nothing about a source makes the analysis throw')]
    public function testAnalysisNeverThrows(): void
    {
        $parser = self::createParser();

        foreach (['', '+', '1 + 2 +', '1 + 2 + 3'] as $source) {
            $parser->analyze(new Source($source));
        }

        $this->expectNotToPerformAssertions();
    }

    #[TestDox('The value of a full analysis is built out of whatever has been recognized')]
    public function testFullModeBuildsTheValue(): void
    {
        $parser = self::createParser([
            self::RULE_NUMBER => static fn(Context $ctx, mixed $children): int => (int) $children->value,
        ]);

        $complete = $parser->analyze(new Source('1 + 2 + 3'), Mode::Tolerant);
        $partial = $parser->analyze(new Source('1 + 2 + 3 -'), Mode::Tolerant);

        self::assertInstanceOf(SuccessfulResult::class, $complete);
        self::assertInstanceOf(PartialResult::class, $partial);

        self::assertSame([1, 2, 3], $complete->value);
        self::assertSame([1, 2, 3], $partial->value, 'The fragment is built the same way');
    }

    #[TestDox('A fast analysis builds nothing but reads the source the same way')]
    public function testFastModeBuildsNothing(): void
    {
        $reduced = false;

        $parser = self::createParser([
            self::RULE_NUMBER => static function (Context $ctx, mixed $children) use (&$reduced): int {
                $reduced = true;

                return (int) $children->value;
            },
        ]);

        $actual = $parser->analyze(new Source('1 + 2 + 3'), Mode::SyntaxCheck);

        self::assertInstanceOf(SuccessfulResult::class, $actual);
        self::assertNull($actual->value);
        self::assertFalse($reduced, 'A fast analysis runs no reducer');
    }

    #[TestDox('The mode does not change how much of the source is read')]
    public function testModeDoesNotChangeRecognition(): void
    {
        $parser = self::createParser();

        foreach (['1 + 2 + 3', '1 + 2 +', '+ 1', ''] as $source) {
            self::assertSame(
                $parser->analyze(new Source($source), Mode::Tolerant)::class,
                $parser->analyze(new Source($source), Mode::SyntaxCheck)::class,
                \sprintf('The source "%s" is expected to be read alike in both modes', $source),
            );
        }
    }

    #[TestDox('A failure tells which tokens the grammar could read instead')]
    public function testFailureReportsExpectedTokens(): void
    {
        $actual = self::createParser()->analyze(new Source('+ 1'), Mode::SyntaxCheck);

        self::assertInstanceOf(FailureResult::class, $actual);
        self::assertCount(1, $actual->diagnostics);

        $diagnostic = $actual->diagnostics[0];

        self::assertSame('Syntax error, unexpected "+" (T_PLUS)', $diagnostic->message);
        self::assertSame([ArithmeticLexer::T_NUMBER], $diagnostic->expected);
    }

    #[TestDox('A partial analysis stops where the fragment ends and reports where the reading broke')]
    public function testPartialReportsWhereTheReadingBroke(): void
    {
        $actual = self::createParser()->analyze(new Source('1 + 2 +'), Mode::SyntaxCheck);

        self::assertInstanceOf(PartialResult::class, $actual);
        self::assertCount(1, $actual->diagnostics);

        // The fragment ends before the operator that opens an iteration the
        // grammar cannot finish
        self::assertSame('T_PLUS', $actual->token->name);
        self::assertSame(6, $actual->token->offset);

        // ...while what is wrong is the operand that never came after it
        $diagnostic = $actual->diagnostics[0];

        self::assertSame(Channel::EndOfInput, $diagnostic->token->channel);
        self::assertSame('Syntax error, unexpected end of input', $diagnostic->message);
        self::assertSame([ArithmeticLexer::T_NUMBER], $diagnostic->expected);
    }

    #[TestDox('An analysis reports the very error an ordinary reading is rejected with')]
    public function testAnalysisReportsTheErrorOfAnOrdinaryReading(): void
    {
        $parser = self::createParser();

        foreach (['1 + 2 +', '1 1', '+ 1', ''] as $source) {
            $result = $parser->analyze(new Source($source), Mode::SyntaxCheck);

            self::assertNotSame(SuccessfulResult::class, $result::class);

            try {
                $parser->parse(new Source($source));

                self::fail(\sprintf('The source "%s" is expected to be rejected', $source));
            } catch (UnexpectedTokenException $e) {
                $error = $result->diagnostics[0]->error;

                self::assertSame($e::class, $error::class);
                self::assertSame($e->getMessage(), $error->getMessage());
                self::assertSame($e->token->offset, $error->token->offset);
                self::assertSame($e->expected, $error->expected);

                // The place an error has been raised at belongs to the trace
                // rather than to the report, and the two are raised apart
                self::assertSame(
                    self::describeError($e),
                    self::describeError($error),
                    'Both print the very same report',
                );
            }
        }
    }

    /**
     * Returns the report of the given error without the trace behind it.
     */
    private static function describeError(UnexpectedTokenException $error): string
    {
        return \explode("\n  thrown in ", (string) $error)[0];
    }

    #[TestDox('The error an analysis reports is thrown as it is')]
    public function testTheReportedErrorIsThrowable(): void
    {
        $result = self::createParser()->analyze(new Source('+ 1'), Mode::SyntaxCheck);

        self::assertInstanceOf(FailureResult::class, $result);

        $this->expectException(UnexpectedTokenException::class);
        $this->expectExceptionMessageIs('Syntax error, unexpected "+" (T_PLUS)');

        throw $result->diagnostics[0]->error;
    }

    #[TestDox('The expected tokens are told without the lookahead tables as well')]
    public function testExpectedTokensWithoutLookaheadTables(): void
    {
        // The tokens a rule may begin with are read off the lookahead table,
        // which a parser is allowed to be given none of
        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: self::createGrammar(),
            initial: self::RULE_EXPRESSION,
        );

        $actual = $parser->analyze(new Source('+ 1'), Mode::SyntaxCheck);

        self::assertInstanceOf(FailureResult::class, $actual);
        self::assertSame([ArithmeticLexer::T_NUMBER], $actual->diagnostics[0]->expected);
    }

    #[TestDox('The tokens of the rules failing alongside each other are told together')]
    public function testExpectedTokensOfSiblingRules(): void
    {
        /** @var list<RuleInterface> $grammar */
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
            lookahead: $analysis->lookahead,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
        );

        $withoutTables = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $grammar,
            initial: 0,
        );

        $expected = [ArithmeticLexer::T_PLUS, ArithmeticLexer::T_MINUS];

        \sort($expected);

        foreach (['with' => $withTables, 'without' => $withoutTables] as $name => $parser) {
            $actual = $parser->analyze(new Source('1'), Mode::SyntaxCheck);

            self::assertInstanceOf(FailureResult::class, $actual);
            self::assertSame(
                $expected,
                $actual->diagnostics[0]->expected,
                \sprintf('Both branches are expected to be told %s the lookahead tables', $name),
            );
        }
    }

    #[TestDox('The tokens of the alternatives never entered are told all the same')]
    public function testExpectedTokensOfAlternativesNeverEntered(): void
    {
        /**
         * Neither alternative is a terminal, so the tokens they may begin with
         * are known from the tables alone, and the tables are what keeps them
         * from being entered on a token that is neither.
         *
         * @var list<RuleInterface> $grammar
         */
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
            lookahead: $analysis->lookahead,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
        );

        $expected = [ArithmeticLexer::T_PLUS, ArithmeticLexer::T_MINUS];

        \sort($expected);

        $actual = $parser->analyze(new Source('1'), Mode::SyntaxCheck);

        self::assertInstanceOf(FailureResult::class, $actual);
        self::assertSame($expected, $actual->diagnostics[0]->expected);
    }

    #[TestDox('An alternation that has recognized none of its alternatives tells the tokens of them all')]
    public function testExpectedTokensOfAnAlternationRecognizingNothing(): void
    {
        /**
         * The first alternative may begin with a minus and refuses to read one,
         * so a minus is what makes the alternation try it, and nothing else,
         * and what makes it fail without a word about itself.
         *
         * @var list<RuleInterface> $grammar
         */
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
            lookahead: $analysis->lookahead,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
        );

        $expected = [ArithmeticLexer::T_PLUS, ArithmeticLexer::T_MINUS];

        \sort($expected);

        $actual = $parser->analyze(new Source('-'), Mode::SyntaxCheck);

        self::assertInstanceOf(FailureResult::class, $actual);
        self::assertSame($expected, $actual->diagnostics[0]->expected);
    }

    #[TestDox('A grammar reading nothing but a repetition reads an empty fragment')]
    public function testEmptyFragmentIsRead(): void
    {
        /** @var list<RuleInterface> $grammar */
        $grammar = [
            0 => new Repetition(1),
            1 => new Lexeme(ArithmeticLexer::T_NUMBER),
        ];

        $analysis = self::analyze($grammar, 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            lookahead: $analysis->lookahead,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
        );

        $actual = $parser->analyze(new Source('+ 1'));

        self::assertInstanceOf(PartialResult::class, $actual);
        self::assertSame([], $actual->value);
        self::assertSame(0, $actual->token->offset, 'Nothing of the source has been read');
    }

    #[TestDox('An empty source is a success in case the grammar reads nothing of it')]
    public function testEmptySourceMayBeSuccessful(): void
    {
        /** @var list<RuleInterface> $grammar */
        $grammar = [
            0 => new Repetition(1),
            1 => new Lexeme(ArithmeticLexer::T_NUMBER),
        ];

        $analysis = self::analyze($grammar, 0);

        $parser = new Parser(
            lexer: new ArithmeticLexer(),
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            lookahead: $analysis->lookahead,
            kept: $analysis->kept,
            choicePrediction: $analysis->choicePrediction,
        );

        $actual = $parser->analyze(new Source(''));

        self::assertInstanceOf(SuccessfulResult::class, $actual);
        self::assertSame([], $actual->value);
        self::assertSame([], $actual->diagnostics);
    }

    private const int RULE_EXPRESSION = 0;

    private const int RULE_NUMBER = 1;

    private const int RULE_OPERATOR = 4;

    /**
     * Expression := Number (("+" | "-") Number)*
     *
     * @return list<RuleInterface>
     */
    private static function createGrammar(): array
    {
        /** @var list<RuleInterface> */
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
     * @param array<int<0, max>, callable(Context, mixed): mixed> $reducers
     */
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
}
