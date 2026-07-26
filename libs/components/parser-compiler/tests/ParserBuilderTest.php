<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Tests;

use Phplrt\Compiler\Parser\ParserBuilder;
use Phplrt\Compiler\Parser\ParserBuilderResult;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class ParserBuilderTest extends TestCase
{
    #[TestDox('The rules are compiled in the order they have been defined')]
    public function testGrammarOrder(): void
    {
        self::assertSame([
            '0: Lexeme(1, keep)',
            '1: Lexeme(2, skip)',
            '2: Lexeme(3, skip)',
            '3: Alternation(1, 2)',
            '4: Concatenation(3, 0)',
            '5: Repetition(4, 0, INF)',
            '6: Concatenation(0, 5)',
        ], self::describe(self::compile()));
    }

    #[TestDox('The names of the rules are available as the identifiers of the grammar')]
    public function testConstants(): void
    {
        self::assertSame([
            'Number' => 0,
            'Operator' => 3,
            'Tail' => 5,
            'Expression' => 6,
        ], self::compile()->constants);
    }

    #[TestDox('The rule marked as the initial one is where the analysis starts')]
    public function testInitialRule(): void
    {
        self::assertSame(6, self::compile()->initial);
    }

    #[TestDox('The analysis starts at the first rule unless another one is marked')]
    public function testDefaultInitialRule(): void
    {
        $parser = new ParserBuilder();
        $parser->tokenName('T_NUMBER');
        $parser->tokenName('T_PLUS');

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertSame(0, $result->initial);
    }

    #[TestDox('A reference is replaced by the rule it points at')]
    public function testReferenceResolution(): void
    {
        $result = self::compile();

        // The "Operator" rule is referred to by its name from the "Tail" rule
        self::assertCount(7, $result->grammar);
        self::assertSame('4: Concatenation(3, 0)', self::describe($result)[4]);
    }

    #[TestDox('The rules that cannot be reached from the initial one are removed')]
    public function testUnreachableRuleRemoval(): void
    {
        $parser = self::createParserBuilder();
        $parser->tokenName('T_MINUS', 'Unused');

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertCount(7, $result->grammar);
        self::assertArrayNotHasKey('Unused', $result->constants);
    }

    #[TestDox('The token names of the grammar are linked to the tokens of the lexer')]
    public function testTokenLinking(): void
    {
        $lexer = self::createLexerBuilder()->build();

        $result = self::createParserBuilder()->build($lexer);

        $number = $result->grammar[0];

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

        self::assertSame([6], \array_keys($result->reducers));
    }

    #[TestDox('The tokens a rule may begin with are computed')]
    public function testLookahead(): void
    {
        $result = self::compile();

        // The expression begins with a number
        self::assertSame([1 => true], $result->first[6]);
        // Any of the operators begins the tail of the expression
        self::assertSame([2 => true, 3 => true], $result->first[5]);
    }

    #[TestDox('The rules that may be recognized without consuming a token are computed')]
    public function testNullable(): void
    {
        $result = self::compile();

        self::assertTrue($result->nullable[5], 'The tail of the expression is optional');
        self::assertFalse($result->nullable[6], 'The expression requires a number');
    }

    #[TestDox('The rules that are present in the resulting tree are computed')]
    public function testKeptRules(): void
    {
        $result = self::compile();

        self::assertTrue($result->kept[6], 'The initial rule is always kept');
        self::assertFalse($result->kept[3], 'An alternation without a reducer passes its value through');
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

        $number = $parser->tokenName('T_NUMBER', 'Number');
        $plus = $parser->tokenName('T_PLUS')->skip();
        $minus = $parser->tokenName('T_MINUS')->skip();

        $parser->choice([$plus, $minus], 'Operator');

        $tail = $parser->repeat(
            rule: $parser->concat([$parser->ref('Operator'), $number]),
            name: 'Tail',
        );

        $parser->setInitialRule($parser->concat([$number, $tail], 'Expression'));

        return $parser;
    }

    private static function compile(): ParserBuilderResult
    {
        return self::createParserBuilder()
            ->build(self::createLexerBuilder()->build());
    }

    /**
     * @return list<string>
     */
    private static function describe(ParserBuilderResult $result): array
    {
        $output = [];

        foreach ($result->grammar as $id => $rule) {
            $output[] = \sprintf('%d: %s', $id, self::describeRule($rule));
        }

        return $output;
    }

    private static function describeRule(RuleInterface $rule): string
    {
        return match (true) {
            $rule instanceof Lexeme => \sprintf('Lexeme(%d, %s)', $rule->tokenId, $rule->keep ? 'keep' : 'skip'),
            $rule instanceof Concatenation => 'Concatenation(' . \implode(', ', $rule->rules) . ')',
            $rule instanceof Alternation => 'Alternation(' . \implode(', ', $rule->ruleIds) . ')',
            $rule instanceof Optional => \sprintf('Optional(%d)', $rule->ruleId),
            $rule instanceof Repetition => \sprintf('Repetition(%d, %d, %s)', $rule->ruleId, $rule->min, $rule->max),
            default => $rule::class,
        };
    }
}
