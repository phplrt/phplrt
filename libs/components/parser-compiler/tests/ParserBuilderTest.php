<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Tests;

use Phplrt\Compiler\Lexer\LexerBuilder;
use Phplrt\Compiler\Parser\Definition\Reducer\PhpCodeReducer;
use Phplrt\Compiler\Parser\Definition\Reducer\ReducerInterface;
use Phplrt\Compiler\Parser\Definition\TokenNameRuleDefinition;
use Phplrt\Compiler\Parser\Exception\ParserCompilerException;
use Phplrt\Compiler\Parser\ParserBuilder;
use Phplrt\Compiler\Parser\ParserBuilderResult;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Lexeme;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class ParserBuilderTest extends TestCase
{
    #[TestDox('The rules are compiled in the order the initial rule reaches them')]
    public function testGrammarOrder(): void
    {
        self::assertSame([
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Repetition(3, 0, INF)',
            '3: Concatenation(4, 1)',
            '4: Alternation(5, 6)',
            '5: Lexeme(2, skip)',
            '6: Lexeme(3, skip)',
        ], self::describe(self::compile()));
    }

    #[TestDox('The names of the rules are available as the identifiers of the grammar')]
    public function testConstants(): void
    {
        self::assertSame([
            'Expression' => 0,
            'Number' => 1,
            'Tail' => 2,
            'Operator' => 4,
        ], self::compile()->constants);
    }

    #[TestDox('The rule marked as the initial one is where the analysis starts')]
    public function testInitialRule(): void
    {
        self::assertSame(0, self::compile()->initial);
    }

    #[TestDox('The analysis starts at the first added rule unless another one is marked')]
    public function testDefaultInitialRule(): void
    {
        $parser = new ParserBuilder();
        $expression = $parser->addConcatenation(name: 'Expression');
        $expression->setRules([$parser->addTokenReference('T_NUMBER')]);

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertSame(0, $result->initial);
        self::assertSame(['Expression' => 0], $result->constants);
    }

    #[TestDox('A rule created on its own is a part of the grammar as soon as another one refers to it')]
    public function testRuleCreatedOutsideOfBuilder(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            new TokenNameRuleDefinition('T_NUMBER'),
        ], 'Root'));

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertCount(2, $result->grammar);
    }

    #[TestDox('A reference is replaced by the rule it points at')]
    public function testReferenceResolution(): void
    {
        $result = self::compile();

        // The "Operator" rule is referred to by its name from the "Tail" rule
        self::assertCount(7, $result->grammar);
        self::assertSame('3: Concatenation(4, 1)', self::describe($result)[3]);
    }

    #[TestDox('The rules that cannot be reached from the initial one are removed')]
    public function testUnreachableRuleRemoval(): void
    {
        $parser = self::createParserBuilder();
        $parser->addTokenReference('T_MINUS', 'Unused');

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertCount(7, $result->grammar);
        self::assertArrayNotHasKey('Unused', $result->constants);
    }

    #[TestDox('The token names of the grammar are linked to the tokens of the lexer')]
    public function testTokenLinking(): void
    {
        $lexer = self::createLexerBuilder()->build();

        $result = self::createParserBuilder()->build($lexer);

        $number = $result->grammar[1];

        self::assertInstanceOf(Lexeme::class, $number);
        self::assertSame($lexer->constants['T_NUMBER'], $number->tokenId);
    }

    #[TestDox('The reducers are indexed by the identifiers of the rules they belong to')]
    public function testReducers(): void
    {
        $parser = self::createParserBuilder();

        self::findRule($parser, 'Expression')
            ->setReducer(static fn(Context $context, mixed $children): mixed => $children);

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertSame([0], \array_keys($result->reducers));
    }

    #[TestDox('A reducer may be defined as PHP code instead of a callback')]
    public function testReducerAsPhpCode(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root')->setReducer(new PhpCodeReducer('return $children;')));

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertSame(['return $children;'], \array_map(
            static fn(ReducerInterface $reducer): string => (string) $reducer,
            $result->reducers,
        ));
    }

    #[TestDox('A parser cannot be created out of the reducers defined as PHP code')]
    public function testReducerAsPhpCodeIsNotExecutable(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root')->setReducer(new PhpCodeReducer('return $children;')));

        $result = $parser->build(self::createLexerBuilder()->build());

        $this->expectException(ParserCompilerException::class);
        $this->expectExceptionMessage('must be generated before it is used');

        $result->createReducerCallbacks();
    }

    #[TestDox('The tokens a rule may begin with are computed')]
    public function testLookahead(): void
    {
        $result = self::compile();

        // The expression begins with a number
        self::assertSame([1 => true], $result->first[0]);
        // Any of the operators begins the tail of the expression
        self::assertSame([2 => true, 3 => true], $result->first[2]);
    }

    #[TestDox('The rules that may be recognized without consuming a token are computed')]
    public function testNullable(): void
    {
        $result = self::compile();

        self::assertTrue($result->nullable[2], 'The tail of the expression is optional');
        self::assertFalse($result->nullable[0], 'The expression requires a number');
    }

    #[TestDox('The rules that are present in the resulting tree are computed')]
    public function testKeptRules(): void
    {
        $result = self::compile();

        self::assertTrue($result->kept[0], 'The initial rule is always kept');
        self::assertFalse($result->kept[4], 'An alternation without a reducer passes its value through');
    }

    #[TestDox('A rule may refer to the token definition instead of its name')]
    public function testTokenDefinitionReference(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('\s++')->hide();
        $number = $lexer->addPattern('\d++');
        $plus = $lexer->addValue('+');

        $parser = new ParserBuilder();
        $digit = $parser->addTokenReference($number);

        $parser->setInitialRule($parser->addConcatenation([
            $digit,
            $parser->addRepetition($parser->addConcatenation([
                $parser->addTokenReference($plus)->skip(),
                $digit,
            ])),
        ]));

        $result = $parser->build($lexer->build());

        self::assertSame([], $result->constants, 'None of the tokens has to be named');
        self::assertSame([
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Repetition(3, 0, INF)',
            '3: Concatenation(4, 1)',
            '4: Lexeme(2, skip)',
        ], self::describe($result));
    }

    #[TestDox('The parser compiled out of the token definitions recognizes the source')]
    public function testTokenDefinitionParsing(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('\s++')->hide();
        $number = $lexer->addPattern('\d++');
        $plus = $lexer->addValue('+');

        $parser = new ParserBuilder();
        $digit = $parser->addTokenReference($number);

        $parser->setInitialRule($parser->addConcatenation([
            $digit,
            $parser->addRepetition($parser->addConcatenation([
                $parser->addTokenReference($plus)->skip(),
                $digit,
            ])),
        ]));

        $compiled = self::createParser(
            lexer: self::createLexer($lexer),
            result: $parser->build($lexer->build()),
        );

        $actual = $compiled->parse('1 + 2 + 3');

        self::assertIsList($actual);

        $values = [];

        foreach ($actual as $token) {
            self::assertInstanceOf(TokenInterface::class, $token);

            $values[] = $token->value;
        }

        self::assertSame(['1', '2', '3'], $values);
    }

    #[TestDox('A rule whose value is the result of the analysis is kept')]
    public function testValueSeenByInitialRule(): void
    {
        $lexer = self::createLexerBuilder();

        // Value := Sum | T_NUMBER
        // Sum   := T_NUMBER "+" Value
        $parser = new ParserBuilder();

        $value = $parser->addAlternation(name: 'Value');

        $sum = $parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addTokenReference('T_PLUS')->skip(),
            $value,
        ], 'Sum');

        $value->setRules([$sum, $parser->addTokenReference('T_NUMBER')]);

        $parser->setInitialRule($value);

        $compiled = self::createParser(
            lexer: self::createLexer($lexer),
            result: $parser->build($lexer->build()),
        );

        /**
         * The value of the sum is joined with the values above it everywhere
         * but the initial rule, which has nothing to join it with.
         */
        self::assertSame(['1', '2', '3'], self::collectValues($compiled->parse('1 + 2 + 3')));
    }

    #[TestDox('The compiled parser recognizes the source')]
    public function testParsing(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = self::createParser(
            lexer: self::createLexer($lexer),
            result: self::createParserBuilder()->build($lexer->build()),
        );

        $actual = $parser->parse('1 + 2 - 3');

        self::assertIsList($actual);

        $values = [];

        foreach ($actual as $token) {
            self::assertInstanceOf(TokenInterface::class, $token);

            $values[] = $token->value;
        }

        self::assertSame(['1', '2', '3'], $values);
    }

    /**
     * Expression := Number (("+" | "-") Number)*
     */
    private static function createParserBuilder(): ParserBuilder
    {
        $parser = new ParserBuilder();

        $number = $parser->addTokenReference('T_NUMBER', 'Number');
        $plus = $parser->addTokenReference('T_PLUS')->skip();
        $minus = $parser->addTokenReference('T_MINUS')->skip();

        $parser->addAlternation([$plus, $minus], 'Operator');

        $tail = $parser->addRepetition(
            rule: $parser->addConcatenation([$parser->addRuleReference('Operator'), $number]),
            name: 'Tail',
        );

        $parser->setInitialRule($parser->addConcatenation([$number, $tail], 'Expression'));

        return $parser;
    }

    private static function compile(): ParserBuilderResult
    {
        return self::createParserBuilder()
            ->build(self::createLexerBuilder()->build());
    }

}
