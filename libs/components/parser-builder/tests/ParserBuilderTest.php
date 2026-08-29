<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\Reducer\ReducerInterface;
use Phplrt\Parser\Builder\Definition\TokenNameRuleDefinition;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Builder\ParserBuilderResult;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser-compiler')]
#[Test]
final class ParserBuilderTest extends TestCase
{
    public function testGrammarOrder(): void
    {
        Assert::same(self::describe(self::compile()), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Repetition(3, 0, INF)',
            '3: Concatenation(4, 1)',
            '4: Alternation(5, 6)',
            '5: Lexeme(2, skip)',
            '6: Lexeme(3, skip)',
        ]);
    }

    public function testConstants(): void
    {
        Assert::same(self::compile()->constants, [
            'Expression' => 0,
            'Number' => 1,
            'Tail' => 2,
            'Operator' => 4,
        ]);
    }

    public function testInitialRule(): void
    {
        Assert::same(self::compile()->initial, 0);
    }

    public function testDefaultInitialRule(): void
    {
        $parser = new ParserBuilder();
        $expression = $parser->addConcatenation(name: 'Expression');
        $expression->setRules([$parser->addTokenReference('T_NUMBER')]);

        $result = $parser->build(self::createLexerBuilder()->build());

        Assert::same($result->initial, 0);
        Assert::same($result->constants, ['Expression' => 0]);
    }

    public function testRuleCreatedOutsideOfBuilder(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            new TokenNameRuleDefinition('T_NUMBER'),
        ], 'Root'));

        $result = $parser->build(self::createLexerBuilder()->build());

        Assert::count($result->grammar, 2);
    }

    public function testReferenceResolution(): void
    {
        $result = self::compile();

        Assert::count($result->grammar, 7);
        Assert::same(self::describe($result)[3], '3: Concatenation(4, 1)');
    }

    public function testUnreachableRuleRemoval(): void
    {
        $parser = self::createParserBuilder();
        $parser->addTokenReference('T_MINUS', 'Unused');

        $result = $parser->build(self::createLexerBuilder()->build());

        Assert::count($result->grammar, 7);
        Assert::array($result->constants)->doesNotHaveKeys('Unused');
    }

    public function testTokenLinking(): void
    {
        $lexer = self::createLexerBuilder()->build();

        $result = self::createParserBuilder()->build($lexer);

        $number = $result->grammar[1];

        Assert::instanceOf($number, Lexeme::class);
        Assert::same($lexer->names[$number->tokenId], 'T_NUMBER');
    }

    public function testReducers(): void
    {
        $parser = self::createParserBuilder();

        self::findRule($parser, 'Expression')
            ->setReducer(static fn(Context $context, mixed $children): mixed => $children);

        $result = $parser->build(self::createLexerBuilder()->build());

        Assert::same(\array_keys($result->reducers), [0]);
    }

    public function testReducerAsPhpCode(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root')->setReducer(new PhpCodeReducer('return $children;')));

        $result = $parser->build(self::createLexerBuilder()->build());

        Assert::same(\array_map(
            static fn(ReducerInterface $reducer): string => (string) $reducer,
            $result->reducers,
        ), ['return $children;']);
    }

    public function testReducerAsPhpCodeIsNotExecutable(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root')->setReducer(new PhpCodeReducer('return $children;')));

        $result = $parser->build(self::createLexerBuilder()->build());

        Assert::true(\array_all(
            $result->reducers,
            static fn(object $reducer): bool => $reducer instanceof PhpCodeReducer,
        ));
        Assert::false($result->reducers[0] instanceof CallableReducer);
    }

    public function testParserWithReducerAsPhpCode(): void
    {
        $parser = self::createParserWithReducer(
            'return \implode(\'+\', \array_map(static fn(mixed $item): string => $item->value, $children));',
        );

        Assert::same($parser->parse(StringSource::createFromString('1 + 2 + 3')), '1+2+3');
    }

    public function testReducerAsPhpCodeContext(): void
    {
        $parser = self::createParserWithReducer('return [$ctx->rule, $ctx->begin, $ctx->source->content];');

        Assert::same($parser->parse(StringSource::createFromString('1 + 2 + 3')), [0, 0, '1 + 2 + 3']);
    }

    public function testReducerAsPhpCodeRequiringObject(): void
    {
        $parser = self::createParserWithReducer('return $this->something;');

        Expect::exception(\Error::class)
        ->withMessage('Using $this when not in object context');

        $parser->parse(StringSource::createFromString('1 + 2 + 3'));
    }

    public function testReducerAsPhpCodeMalformed(): void
    {
        Expect::exception(ParserCompilerException::class)
        ->withMessageContaining('The reducer of the rule Root cannot be compiled: ');

        self::createParserWithReducer('return $children');
    }

    private static function createParserWithReducer(string $code): ParserInterface
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addRepetition($parser->addConcatenation([
                $parser->addTokenReference('T_PLUS')->skip(),
                $parser->addTokenReference('T_NUMBER'),
            ])),
        ], 'Root')->setReducer(new PhpCodeReducer($code)));

        return $parser->build($lexer->build())
            ->toParser(self::createLexer($lexer));
    }

    public function testLookahead(): void
    {
        $result = self::compile();

        Assert::same($result->lookahead[0], [1 => true]);
    }

    public function testNullable(): void
    {
        $result = self::compile();

        Assert::null($result->lookahead[2]);
        Assert::notNull($result->lookahead[0], 'The expression requires a number');
    }

    public function testKeptRules(): void
    {
        $result = self::compile();

        Assert::true($result->kept[0], 'The initial rule is always kept');
        Assert::false($result->kept[4], 'An alternation without a reducer passes its value through');
    }

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

        Assert::same($result->constants, [], 'None of the tokens has to be named');
        Assert::same(self::describe($result), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Repetition(3, 0, INF)',
            '3: Concatenation(4, 1)',
            '4: Lexeme(2, skip)',
        ]);
    }

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

        $actual = $compiled->parse(StringSource::createFromString('1 + 2 + 3'));

        Assert::array($actual)->isList();

        $values = [];

        foreach ($actual as $token) {
            Assert::instanceOf($token, TokenInterface::class);

            $values[] = $token->value;
        }

        Assert::same($values, ['1', '2', '3']);
    }

    public function testValueSeenByInitialRule(): void
    {
        $lexer = self::createLexerBuilder();

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

        Assert::same(self::collectValues($compiled->parse(StringSource::createFromString('1 + 2 + 3'))), ['1', '2', '3']);
    }

    public function testParsing(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = self::createParser(
            lexer: self::createLexer($lexer),
            result: self::createParserBuilder()->build($lexer->build()),
        );

        $actual = $parser->parse(StringSource::createFromString('1 + 2 - 3'));

        Assert::array($actual)->isList();

        $values = [];

        foreach ($actual as $token) {
            Assert::instanceOf($token, TokenInterface::class);

            $values[] = $token->value;
        }

        Assert::same($values, ['1', '2', '3']);
    }

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
