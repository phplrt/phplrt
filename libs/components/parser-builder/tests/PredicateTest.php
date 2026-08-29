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
final class PredicateTest extends TestCase
{
    private static function createParserFor(bool $isExpected): ParserInterface
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addPredicate($parser->addTokenReference('T_NUMBER'), $isExpected),
            $parser->addAlternation([
                $parser->addTokenReference('T_NUMBER'),
                $parser->addTokenReference('T_PLUS'),
            ]),
        ], 'Root'));

        return $parser->build($lexer->build())
            ->toParser(self::createLexer($lexer));
    }

    public function testExpectedRuleIsRecognized(): void
    {
        $parser = self::createParserFor(true);

        Assert::same(self::collectValues($parser->parse(StringSource::createFromString('1'))), ['1']);
    }

    public function testPredicateReadsNothing(): void
    {
        $parser = self::createParserFor(true);

        Assert::count(self::collectValues($parser->parse(StringSource::createFromString('1'))), 1);
    }

    public function testExpectedRuleIsNotRecognized(): void
    {
        $parser = self::createParserFor(true);

        Expect::exception(UnexpectedTokenException::class);

        $parser->parse(StringSource::createFromString('+'));
    }

    public function testRejectedRuleIsRecognized(): void
    {
        $parser = self::createParserFor(false);

        Assert::same(self::collectValues($parser->parse(StringSource::createFromString('+'))), ['+']);
    }

    public function testRejectedRuleIsNotRecognized(): void
    {
        $parser = self::createParserFor(false);

        Expect::exception(UnexpectedTokenException::class);

        $parser->parse(StringSource::createFromString('1'));
    }

    public function testGrammar(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addPredicate($parser->addTokenReference('T_PLUS'), isExpected: false),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        Assert::same(self::describe($parser->build($lexer->build())), [
            '0: Concatenation(1, 3)',
            '1: Predicate(2, reject)',
            '2: Lexeme(2, keep)',
            '3: Lexeme(1, keep)',
        ]);
    }

    public function testPredicateWithReducer(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addPredicate($parser->addTokenReference('T_NUMBER'))
                ->setReducer(static fn(): bool => true),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        Expect::exception(ParserCompilerException::class)
        ->withMessageContaining('only looks at what comes next, so it builds nothing to reduce');

        $parser->build(self::createLexerBuilder()->build());
    }

    public function testLeftRecursionThroughPredicate(): void
    {
        $parser = new ParserBuilder();
        $root = $parser->addConcatenation(name: 'Root');
        $root->setRules([
            $parser->addPredicate($root),
            $parser->addTokenReference('T_NUMBER'),
        ]);

        $parser->setInitialRule($root);

        Expect::exception(ParserCompilerException::class)
        ->withMessageContaining('is left recursive');

        $parser->build(self::createLexerBuilder()->build());
    }
}
