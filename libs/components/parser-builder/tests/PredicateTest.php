<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class PredicateTest extends TestCase
{
    /**
     * Reads a number or a plus, but only where the predicate allows it.
     */
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

    #[TestDox('A rule behind a predicate is recognized while the predicate matches')]
    public function testExpectedRuleIsRecognized(): void
    {
        $parser = self::createParserFor(true);

        self::assertSame(['1'], self::collectValues($parser->parse(new Source('1'))));
    }

    #[TestDox('A predicate reads nothing, so the rule behind it reads the very same token')]
    public function testPredicateReadsNothing(): void
    {
        $parser = self::createParserFor(true);

        // The number would be read twice in case of the predicate consumed it
        self::assertCount(1, self::collectValues($parser->parse(new Source('1'))));
    }

    #[TestDox('A rule behind a predicate is not recognized while the predicate does not match')]
    public function testExpectedRuleIsNotRecognized(): void
    {
        $parser = self::createParserFor(true);

        $this->expectException(UnexpectedTokenException::class);

        $parser->parse(new Source('+'));
    }

    #[TestDox('A negative predicate matches everything the rule does not')]
    public function testRejectedRuleIsRecognized(): void
    {
        $parser = self::createParserFor(false);

        self::assertSame(['+'], self::collectValues($parser->parse(new Source('+'))));
    }

    #[TestDox('A negative predicate matches nothing the rule does')]
    public function testRejectedRuleIsNotRecognized(): void
    {
        $parser = self::createParserFor(false);

        $this->expectException(UnexpectedTokenException::class);

        $parser->parse(new Source('1'));
    }

    #[TestDox('A predicate is compiled into a rule of its own')]
    public function testGrammar(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addPredicate($parser->addTokenReference('T_PLUS'), isExpected: false),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        self::assertSame([
            '0: Concatenation(1, 3)',
            '1: Predicate(2, reject)',
            '2: Lexeme(2, keep)',
            '3: Lexeme(1, keep)',
        ], self::describe($parser->build($lexer->build())));
    }

    #[TestDox('A predicate builds nothing, so it cannot be reduced')]
    public function testPredicateWithReducer(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addPredicate($parser->addTokenReference('T_NUMBER'))
                ->setReducer(static fn(): bool => true),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        $this->expectException(ParserCompilerException::class);
        $this->expectExceptionMessageIsOrContains('only looks at what comes next, so it builds nothing to reduce');

        $parser->build(self::createLexerBuilder()->build());
    }

    #[TestDox('A rule reaching itself through a predicate is left recursive')]
    public function testLeftRecursionThroughPredicate(): void
    {
        $parser = new ParserBuilder();
        $root = $parser->addConcatenation(name: 'Root');
        $root->setRules([
            $parser->addPredicate($root),
            $parser->addTokenReference('T_NUMBER'),
        ]);

        $parser->setInitialRule($root);

        $this->expectException(ParserCompilerException::class);
        $this->expectExceptionMessageIsOrContains('is left recursive');

        $parser->build(self::createLexerBuilder()->build());
    }
}
