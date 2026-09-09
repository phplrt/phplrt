<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser-compiler')]
#[Test]
final class AdjacencyTest extends TestCase
{
    /**
     * Root : <T_NUMBER> ~ <T_PLUS> <T_NUMBER> ;
     */
    private static function createParserFor(bool $isExpected): ParserInterface
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addAdjacency($isExpected),
            $parser->addTokenReference('T_PLUS'),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        return $parser->build($lexer->build())
            ->toParser(self::createLexer($lexer));
    }

    public function testTokensWrittenNextToEachOtherAreRecognized(): void
    {
        $parser = self::createParserFor(true);

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('1+2'))),
            ['1', '+', '2'],
        );
    }

    public function testSeparatedTokensAreNotRecognized(): void
    {
        $parser = self::createParserFor(true);

        Expect::exception(UnexpectedTokenException::class);

        $parser->parse(StringSource::createFromString('1 + 2'));
    }

    public function testAdjacencyReadsNothing(): void
    {
        $parser = self::createParserFor(true);

        Assert::count(self::collectValues($parser->parse(StringSource::createFromString('1+2'))), 3);
    }

    public function testSeparatedTokensAreRecognizedWhenTheGapIsRequired(): void
    {
        $parser = self::createParserFor(false);

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('1 + 2'))),
            ['1', '+', '2'],
        );
    }

    public function testTokensWrittenNextToEachOtherAreNotRecognizedWhenTheGapIsRequired(): void
    {
        $parser = self::createParserFor(false);

        Expect::exception(UnexpectedTokenException::class);

        $parser->parse(StringSource::createFromString('1+2'));
    }

    /**
     * Root : (<T_NUMBER> ~ <T_PLUS> ~)* <T_NUMBER> ;
     *
     * A sequence may end with the rule: what it compares is the pair its own
     * last token makes with whatever the next turn begins with.
     */
    public function testAdjacencyClosingARepeatedSequence(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRepetition($parser->addConcatenation([
                $parser->addTokenReference('T_NUMBER'),
                $parser->addAdjacency(),
                $parser->addTokenReference('T_PLUS'),
                $parser->addAdjacency(),
            ])),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        $runtime = $parser->build($lexer->build())
            ->toParser(self::createLexer($lexer));

        Assert::same(
            self::collectValues($runtime->parse(StringSource::createFromString('1+2+3'))),
            ['1', '+', '2', '+', '3'],
        );

        Expect::exception(UnexpectedTokenException::class);

        $runtime->parse(StringSource::createFromString('1+2 +3'));
    }

    /**
     * Root : <T_NUMBER> ~ <T_MINUS> | <T_NUMBER> <T_MINUS> ;
     *
     * The rule reads nothing, so an alternative rejected by it leaves neither
     * the input nor the trace behind.
     */
    public function testRejectedAlternativeLeavesNothingBehind(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addAlternation([
            $parser->addConcatenation([
                $parser->addTokenReference('T_NUMBER'),
                $parser->addAdjacency(),
                $parser->addTokenReference('T_MINUS'),
            ]),
            $parser->addConcatenation([
                $parser->addTokenReference('T_NUMBER'),
                $parser->addTokenReference('T_MINUS'),
            ]),
        ], 'Root'));

        $runtime = $parser->build($lexer->build())
            ->toParser(self::createLexer($lexer));

        Assert::same(
            self::collectValues($runtime->parse(StringSource::createFromString('1 -'))),
            ['1', '-'],
        );
    }

    public function testGrammar(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addAdjacency(isExpected: false),
            $parser->addTokenReference('T_PLUS'),
        ], 'Root'));

        Assert::same(self::describe($parser->build($lexer->build())), [
            '0: Concatenation(1, 2, 3)',
            '1: Lexeme(1, keep)',
            '2: Adjacency(reject)',
            '3: Lexeme(2, keep)',
        ]);
    }

    public function testAdjacencyOpeningASequenceIsReported(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addAdjacency(),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        Expect::exception(ParserCompilerException::class)
        ->withMessageContaining('it cannot open the sequence');

        $parser->build(self::createLexerBuilder()->build());
    }

    public function testNullableStatementBeforeAdjacencyIsReported(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addOptional($parser->addTokenReference('T_PLUS')),
            $parser->addAdjacency(),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        Expect::exception(ParserCompilerException::class)
        ->withMessageContaining('may be recognized without reading one');

        $parser->build(self::createLexerBuilder()->build());
    }

    public function testAdjacencyAsARuleOfItsOwnIsReported(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addAdjacency(name: 'Root'));

        Expect::exception(ParserCompilerException::class)
        ->withMessageContaining('cannot be a rule of its own');

        $parser->build(self::createLexerBuilder()->build());
    }

    public function testAdjacencyOutsideOfASequenceIsReported(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addRepetition(
            $parser->addAdjacency(),
            min: 1,
            name: 'Root',
        ));

        Expect::exception(ParserCompilerException::class)
        ->withMessageContaining('may only be written after a statement of a sequence');

        $parser->build(self::createLexerBuilder()->build());
    }
}
