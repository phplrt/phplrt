<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Exception\UnsupportedAnnotationException;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP3AnnotationTest extends TestCase
{
    private const string HEAD = <<<'PP3'
        %token T_OPEN   \(
        %token T_CLOSE  \)
        %token T_COMMA  ,
        %token T_NAME   [a-z]++
        %skip  T_SPACE  \s++
        %pragma root Root

        PP3;

    public function testMessageOfAStatementIsReported(): void
    {
        $actual = self::read(
            'Root : ::T_OPEN:: <T_NAME> ::T_CLOSE:: @error("a closing parenthesis is expected") ;',
            '(abc',
        );

        Assert::same($actual, 'a closing parenthesis is expected');
    }

    public function testMessageOfARuleIsReported(): void
    {
        $actual = self::read(
            'Root @error("a group is expected") : ::T_OPEN:: <T_NAME> ::T_CLOSE:: ;',
            'abc',
        );

        Assert::same($actual, 'a group is expected');
    }

    public function testMessageOfARuleIsReportedWhenTheSourceIsNotReadToItsEnd(): void
    {
        $actual = self::read(
            'Root @error("a group is expected") : ::T_OPEN:: <T_NAME> ::T_CLOSE:: ;',
            '(abc) def',
        );

        Assert::same($actual, 'a group is expected');
    }

    public function testMessageOfARuleWithAReducerIsReported(): void
    {
        $actual = self::read(
            'Root @error("a group is expected") -> { return $children; }'
                . ' : ::T_OPEN:: <T_NAME> ::T_CLOSE:: ;',
            'abc',
        );

        Assert::same($actual, 'a group is expected');
    }

    public function testMessageOfAReferenceIsReported(): void
    {
        $actual = self::read(
            'Root : ::T_OPEN:: Pair() @error("a pair of names is expected") ::T_CLOSE:: ;'
                . "\nPair : <T_NAME> ::T_COMMA:: <T_NAME> ;",
            '(a, )',
        );

        Assert::same($actual, 'a pair of names is expected');
    }

    public function testInnermostMessageIsReported(): void
    {
        $actual = self::read(
            'Root : ::T_OPEN:: Pair() @error("outer") ::T_CLOSE:: ;'
                . "\nPair : <T_NAME> ::T_COMMA:: <T_NAME> @error(\"inner\") ;",
            '(a, )',
        );

        Assert::same($actual, 'inner');
    }

    public function testDeeperFailureIsReportedOverAnEarlierMessage(): void
    {
        $actual = self::read(
            'Root : ::T_OPEN:: Pair() @error("a pair of names is expected") ::T_CLOSE:: <T_NAME> ;'
                . "\nPair : <T_NAME> ::T_COMMA:: <T_NAME> ;",
            '(a, b) ,',
        );

        Assert::same($actual, 'Syntax error, unexpected "," (T_COMMA), T_NAME expected');
    }

    public function testMessageIsReportedBeforeAPredicate(): void
    {
        $actual = self::read(
            'Root : ::T_OPEN:: &<T_NAME> @error("a name is expected") <T_NAME> ::T_CLOSE:: ;',
            '(,)',
        );

        Assert::same($actual, 'a name is expected');
    }

    public function testPlaceholderIsFilledIn(): void
    {
        $actual = self::read(
            'Root : ::T_OPEN:: <T_NAME> ::T_CLOSE:: @error("got {name} at {line}:{column}") ;',
            "(abc\n  ,",
        );

        Assert::same($actual, 'got T_COMMA at 2:3');
    }

    #[DataSet(['{expected}', 'T_OPEN, T_CLOSE, T_COMMA (+1 more)'], 'shortened')]
    #[DataSet(['{expected_list}', 'T_OPEN, T_CLOSE, T_COMMA or T_NAME'], 'full')]
    public function testExpectedTokensAreListed(string $placeholder, string $expected): void
    {
        $actual = self::read(
            \sprintf('Root : ::T_OPEN:: Item() @error("%s") ;', $placeholder)
                . "\nItem : <T_NAME> | <T_COMMA> | <T_CLOSE> | <T_OPEN> ;",
            '(',
        );

        Assert::same($actual, $expected);
    }

    public function testDoubledBraceStandsForABraceOfTheMessage(): void
    {
        $actual = self::read(
            'Root : ::T_OPEN:: <T_NAME> ::T_CLOSE:: @error("write {{name}} to name it") ;',
            '(abc',
        );

        Assert::same($actual, 'write {name} to name it');
    }

    public function testMessageIsCompiledIntoTheTable(): void
    {
        $result = self::compile(
            'Root : ::T_OPEN:: <T_NAME> ::T_CLOSE:: @error("a closing parenthesis is expected") ;',
        );

        Assert::contains($result->parser->messages, 'a closing parenthesis is expected');
    }

    public function testMessageIsWrittenIntoTheGeneratedParser(): void
    {
        $compiler = new Compiler();
        $compiler->load(self::createGrammar(
            'Root : ::T_OPEN:: <T_NAME> ::T_CLOSE:: @error("a closing parenthesis is expected") ;',
        ));

        Assert::string((string) $compiler->generate())
            ->contains('a closing parenthesis is expected');
    }

    public function testGrammarWithoutAnnotationsCompilesIntoNoMessages(): void
    {
        $result = self::compile('Root : ::T_OPEN:: <T_NAME> ::T_CLOSE:: ;');

        Assert::same($result->parser->messages, []);
    }

    public function testUnknownAnnotationIsReported(): void
    {
        Expect::exception(UnsupportedAnnotationException::class)
        ->withMessage('Unrecognized annotation "@warning"');

        self::compile('Root : ::T_OPEN:: <T_NAME> @warning("nope") ::T_CLOSE:: ;');
    }

    #[DataSet(['@error()', 'The "@error" annotation expects 1 value(s), 0 given'], 'none')]
    #[DataSet(['@error("a", "b")', 'The "@error" annotation expects 1 value(s), 2 given'], 'two')]
    public function testAnnotationWithWrongNumberOfValuesIsReported(string $annotation, string $message): void
    {
        Expect::exception(UnsupportedAnnotationException::class)
        ->withMessage($message);

        self::compile(\sprintf('Root : ::T_OPEN:: <T_NAME> %s ::T_CLOSE:: ;', $annotation));
    }

    public function testEmptyMessageIsReported(): void
    {
        Expect::exception(UnsupportedAnnotationException::class)
        ->withMessage('The "@error" annotation expects a value that is not empty');

        self::compile('Root : ::T_OPEN:: <T_NAME> @error("") ::T_CLOSE:: ;');
    }

    public function testRepeatedAnnotationIsReported(): void
    {
        Expect::exception(UnsupportedAnnotationException::class)
        ->withMessage('The "@error" annotation is said about the same rule twice');

        self::compile('Root : ::T_OPEN:: <T_NAME> @error("a") @error("b") ::T_CLOSE:: ;');
    }

    public function testUnknownPlaceholderIsReported(): void
    {
        Expect::exception(UnsupportedAnnotationException::class)
        ->withMessage('Unrecognized placeholder "{reason}" of the "@error" annotation, one of '
            . '"{token}", "{name}", "{value}", "{offset}", "{line}", "{column}", "{expected}", '
            . '"{expected_list}" expected');

        self::compile('Root : ::T_OPEN:: <T_NAME> @error("because {reason}") ::T_CLOSE:: ;');
    }

    #[DataSet([
        'Root : <T_NAME> @error("nope") ::T_CLOSE:: ;',
        'the rule containing it is rejected by this very token before it is entered',
    ], 'first element')]
    #[DataSet([
        'Root : ::T_OPEN:: Choice() ::T_CLOSE:: ;' . "\nChoice : <T_NAME> @error(\"nope\") | <T_COMMA> ;",
        'the rule containing it is rejected by this very token before it is entered',
    ], 'guarded alternative')]
    #[DataSet([
        'Root : ::T_OPEN:: <T_NAME>? @error("nope") ::T_CLOSE:: ;',
        'the rule is recognized even when the input does not match it',
    ], 'optional')]
    #[DataSet([
        'Root : ::T_OPEN:: <T_NAME>* @error("nope") ::T_CLOSE:: ;',
        'the rule is recognized even when the input does not match it',
    ], 'repetition')]
    #[DataSet([
        'Root : ::T_OPEN:: (<T_NAME> @error("nope"))* ::T_CLOSE:: ;',
        'nothing reports the failure of a rule written in this place',
    ], 'inside a repetition')]
    public function testMessageThatCanNeverBeReportedIsReported(string $body, string $reason): void
    {
        Assert::string(self::compileFailure($body))
            ->contains('can never report the message it carries')
            ->contains($reason);
    }

    public function testMessageOfAnAlternativeOfANullableChoiceIsReportable(): void
    {
        $result = self::compile(
            'Root : ::T_OPEN:: Choice() <T_NAME> ::T_CLOSE:: ;'
                . "\nChoice : <T_COMMA> @error(\"nope\") | <T_NAME>? ;",
        );

        Assert::contains($result->parser->messages, 'nope');
    }

    public function testMessageOfARuleReadInSeveralPlacesIsReportable(): void
    {
        $result = self::compile(
            'Root : Head() ::T_COMMA:: Head() ::T_CLOSE:: ;'
                . "\nHead @error(\"nope\") : <T_NAME> ;",
        );

        Assert::contains($result->parser->messages, 'nope');
    }

    public function testAnnotationWithoutParenthesesIsNotRead(): void
    {
        Expect::exception(UnexpectedTokenException::class);

        self::compile('Root : ::T_OPEN:: <T_NAME> @error ::T_CLOSE:: ;');
    }

    private static function createGrammar(string $body): StringSource
    {
        return new StringSource(self::HEAD . $body, '/app/grammar.pp3');
    }

    private static function compile(string $body): \Phplrt\Compiler\CompilerResult
    {
        $compiler = new Compiler();
        $compiler->load(self::createGrammar($body));

        return $compiler->build();
    }

    private static function compileFailure(string $body): string
    {
        try {
            self::compile($body);
        } catch (CompilationFailedException $e) {
            return $e->getMessage();
        }

        Assert::fail('The grammar is expected to break the compilation');
    }

    private static function read(string $body, string $source): string
    {
        $compiler = new Compiler();
        $compiler->load(self::createGrammar($body));

        try {
            $compiler->getParser()
                ->parse(new StringSource($source, '/app/input.txt'));
        } catch (UnexpectedTokenException $e) {
            return $e->getMessage();
        }

        Assert::fail('The source is expected to break the reading');
    }
}
