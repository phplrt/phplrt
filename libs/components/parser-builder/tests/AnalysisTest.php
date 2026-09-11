<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Builder\ParserBuilderResult;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser-compiler')]
#[Test]
final class AnalysisTest extends TestCase
{
    public function testOptionalElementIsReadInPlace(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addOptional($parser->addTokenReference('T_PLUS')),
            $parser->addTokenReference('T_NUMBER'),
        ]));

        $result = self::compile($parser);

        Assert::same(self::describe($result), [
            '0: Concatenation(1, 2, 1)',
            '1: Lexeme(1, keep)',
            '2: Optional(3)',
            '3: Lexeme(2, keep)',
        ]);

        // The optional #2 is written as the rule #3 it wraps, negated
        Assert::same($result->sequencePrediction, [0 => [1, -4, 1]]);
    }

    public function testKeptOptionalIsLeftAsItIs(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addOptional($parser->addTokenReference('T_PLUS'), 'Sign')
                ->setReducer(new CallableReducer(static fn(mixed $context, mixed $children): mixed => $children)),
        ]));

        $result = self::compile($parser);

        Assert::true($result->kept[2], 'An optional with a reducer becomes a node');
        Assert::same($result->sequencePrediction, [], 'A sequence written as it is declared is not listed');
    }

    public function testSequenceWithoutOptionalElementsIsNotListed(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addRepetition($parser->addTokenReference('T_PLUS')),
        ]));

        Assert::same(self::compile($parser)->sequencePrediction, []);
    }

    public function testOptionalElementsAreReadEitherWay(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addOptional($parser->addTokenReference('T_PLUS')),
            $parser->addTokenReference('T_NUMBER'),
        ]));

        $runtime = self::createParser(self::createLexer($lexer), $parser->build($lexer->build()));

        Assert::same(self::collectValues($runtime->parse(StringSource::createFromString('1 + 2'))), ['1', '+', '2']);
        Assert::same(self::collectValues($runtime->parse(StringSource::createFromString('1 2'))), ['1', '2']);
    }

    public function testPredictedTerminalIsNegated(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addAlternation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addTokenReference('T_PLUS'),
        ]));

        $result = self::compile($parser);

        Assert::same(self::describe($result), [
            '0: Alternation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, keep)',
        ]);

        // Each token predicts the very terminal that reads it, negated
        Assert::same($result->choicePrediction, [0 => [1 => [-2], 2 => [-3]]]);
    }

    public function testPredictedProductionIsNotNegated(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addAlternation([
            $parser->addConcatenation([
                $parser->addTokenReference('T_NUMBER'),
                $parser->addTokenReference('T_MINUS'),
            ]),
            $parser->addTokenReference('T_PLUS'),
        ]));

        $result = self::compile($parser);

        Assert::same(self::describe($result), [
            '0: Alternation(1, 4)',
            '1: Concatenation(2, 3)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(3, keep)',
            '4: Lexeme(2, keep)',
        ]);

        Assert::same($result->choicePrediction, [0 => [1 => [1], 2 => [-5]]]);
    }

    public function testPredictedTerminalsAreRead(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addRepetition($parser->addAlternation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addTokenReference('T_PLUS'),
        ]), min: 1));

        $runtime = self::createParser(self::createLexer($lexer), $parser->build($lexer->build()));

        Assert::same(self::collectValues($runtime->parse(StringSource::createFromString('1 + 2 +'))), ['1', '+', '2', '+']);
    }

    private static function compile(ParserBuilder $parser): ParserBuilderResult
    {
        return $parser->build(self::createLexerBuilder()->build());
    }
}
