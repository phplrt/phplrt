<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Builder\ParserBuilderResult;
use Phplrt\Parser\Context;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser-compiler')]
#[Test]
final class OptimizationTest extends TestCase
{
    public function testSingleAlternative(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addAlternation([$parser->addTokenReference('T_NUMBER')]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ]);
    }

    public function testSingleConcatenation(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ]);
    }

    public function testStandaloneConcatenation(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addAlternation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Alternation(1, 3)',
            '1: Concatenation(2)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ]);
    }

    public function testNestedConcatenation(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addConcatenation([
                $parser->addTokenReference('T_PLUS')->skip(),
                $parser->addTokenReference('T_MINUS')->skip(),
            ]),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 2, 3)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
            '3: Lexeme(3, skip)',
        ]);
    }

    public function testNestedAlternation(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addAlternation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addAlternation([
                $parser->addTokenReference('T_PLUS')->skip(),
                $parser->addTokenReference('T_MINUS')->skip(),
            ]),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Alternation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Alternation(3, 4)',
            '3: Lexeme(2, skip)',
            '4: Lexeme(3, skip)',
        ]);
    }

    public function testSharedConcatenation(): void
    {
        $parser = new ParserBuilder();

        $shared = $parser->addConcatenation([
            $parser->addTokenReference('T_PLUS')->skip(),
            $parser->addTokenReference('T_MINUS')->skip(),
        ]);

        $parser->setInitialRule($parser->addConcatenation([
            $shared,
            $parser->addTokenReference('T_NUMBER'),
            $shared,
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 4, 1)',
            '1: Concatenation(2, 3)',
            '2: Lexeme(2, skip)',
            '3: Lexeme(3, skip)',
            '4: Lexeme(1, keep)',
        ]);
    }

    public function testDuplicateRules(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addRepetition($parser->addConcatenation([
                $parser->addTokenReference('T_PLUS')->skip(),
                $parser->addTokenReference('T_NUMBER'),
            ])),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Repetition(3, 0, INF)',
            '3: Concatenation(4, 1)',
            '4: Lexeme(2, skip)',
        ]);
    }

    public function testDifferentOccurrences(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addTokenReference('T_NUMBER')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(1, skip)',
        ]);
    }

    public function testSingleRepetition(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRepetition($parser->addTokenReference('T_NUMBER'), max: 1, min: 1),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ]);
    }

    public function testOptionalOfAnAlwaysMatchingRule(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addOptional($parser->addRepetition($parser->addTokenReference('T_NUMBER'))),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 3)',
            '1: Repetition(2, 0, INF)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ]);
    }

    public function testOptionalOfAFailingRule(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addOptional($parser->addRepetition($parser->addTokenReference('T_NUMBER'), min: 1)),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 4)',
            '1: Optional(2)',
            '2: Repetition(3, 1, INF)',
            '3: Lexeme(1, keep)',
            '4: Lexeme(2, skip)',
        ]);
    }

    public function testNestedRepetition(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRepetition(
                $parser->addRepetition($parser->addTokenReference('T_NUMBER'), min: 1),
            ),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 3)',
            '1: Repetition(2, 0, INF)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ]);
    }

    public function testNestedRepetitionOfSeveralOccurrences(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRepetition(
                $parser->addRepetition($parser->addTokenReference('T_NUMBER'), min: 1),
                min: 2,
            ),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 4)',
            '1: Repetition(2, 2, INF)',
            '2: Repetition(3, 1, INF)',
            '3: Lexeme(1, keep)',
            '4: Lexeme(2, skip)',
        ]);
    }

    public function testRepeatedAlternative(): void
    {
        $parser = new ParserBuilder();

        $number = $parser->addTokenReference('T_NUMBER');

        $parser->setInitialRule($parser->addConcatenation([
            $parser->addAlternation([
                $number,
                $parser->addTokenReference('T_MINUS')->skip(),
                $number,
            ]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 4)',
            '1: Alternation(2, 3)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(3, skip)',
            '4: Lexeme(2, skip)',
        ]);
    }

    public function testRepeatedAlternativeWrittenTwice(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addAlternation([
                $parser->addTokenReference('T_NUMBER'),
                $parser->addTokenReference('T_MINUS')->skip(),
                $parser->addTokenReference('T_NUMBER'),
            ]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 4)',
            '1: Alternation(2, 3)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(3, skip)',
            '4: Lexeme(2, skip)',
        ]);
    }

    public function testNamedRule(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')], 'Group'),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        $result = self::compile($parser);

        Assert::same(self::describe($result), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ]);
        Assert::same($result->constants, []);
    }

    public function testNamedRuleWithReducer(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')], 'Group')
                ->setReducer(static fn(Context $context, mixed $children): mixed => $children),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        $result = self::compile($parser);

        Assert::same(self::describe($result), [
            '0: Concatenation(1, 3)',
            '1: Concatenation(2)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ]);
        Assert::same($result->constants, ['Group' => 1]);
    }

    public function testRuleReachedThroughAnAlternation(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addAlternation([
                $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')], 'Number'),
                $parser->addTokenReference('T_MINUS')->skip(),
            ]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        $result = self::compile($parser);

        Assert::same(self::describe($result), [
            '0: Concatenation(1, 4)',
            '1: Alternation(2, 3)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(3, skip)',
            '4: Lexeme(2, skip)',
        ]);
        Assert::same($result->constants, []);
    }

    public function testRuleGivenToAReducer(): void
    {
        $parser = new ParserBuilder();

        $parser->setInitialRule($parser->addConcatenation([
            $parser->addAlternation([
                $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')], 'Number'),
                $parser->addTokenReference('T_MINUS')->skip(),
            ])->setReducer(static fn(Context $context, mixed $children): mixed => $children),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        $result = self::compile($parser);

        Assert::same(self::describe($result), [
            '0: Concatenation(1, 5)',
            '1: Alternation(2, 4)',
            '2: Concatenation(3)',
            '3: Lexeme(1, keep)',
            '4: Lexeme(3, skip)',
            '5: Lexeme(2, skip)',
        ]);
        Assert::same($result->constants, ['Number' => 2]);
    }

    public function testRuleWithReducer(): void
    {
        $parser = new ParserBuilder();

        $group = $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')])
            ->setReducer(static fn(Context $context, mixed $children): mixed => $children);

        $parser->setInitialRule($parser->addConcatenation([
            $group,
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        Assert::same(self::describe(self::compile($parser)), [
            '0: Concatenation(1, 3)',
            '1: Concatenation(2)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ]);
    }

    public function testCanonicalForm(): void
    {
        $verbose = new ParserBuilder();
        $verbose->setInitialRule($verbose->addConcatenation([
            $verbose->addAlternation([$verbose->addTokenReference('T_NUMBER')]),
            $verbose->addRepetition($verbose->addConcatenation([
                $verbose->addConcatenation([$verbose->addAlternation([
                    $verbose->addTokenReference('T_PLUS')->skip(),
                    $verbose->addTokenReference('T_MINUS')->skip(),
                ])]),
                $verbose->addTokenReference('T_NUMBER'),
            ])),
        ]));

        $plain = new ParserBuilder();
        $number = $plain->addTokenReference('T_NUMBER');
        $plain->setInitialRule($plain->addConcatenation([
            $number,
            $plain->addRepetition($plain->addConcatenation([
                $plain->addAlternation([
                    $plain->addTokenReference('T_PLUS')->skip(),
                    $plain->addTokenReference('T_MINUS')->skip(),
                ]),
                $number,
            ])),
        ]));

        Assert::same(self::describe(self::compile($verbose)), self::describe(self::compile($plain)));
    }

    public function testParsing(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addAlternation([$parser->addTokenReference('T_NUMBER')]),
            $parser->addRepetition($parser->addConcatenation([
                $parser->addConcatenation([$parser->addAlternation([
                    $parser->addTokenReference('T_PLUS')->skip(),
                    $parser->addTokenReference('T_MINUS')->skip(),
                ])]),
                $parser->addTokenReference('T_NUMBER'),
            ])),
        ]));

        $compiled = self::createParser(
            lexer: self::createLexer($lexer),
            result: $parser->build($lexer->build()),
        );

        $actual = $compiled->parse(StringSource::createFromString('1 + 2 - 3'));

        Assert::array($actual)->isList();

        $values = [];

        foreach ($actual as $token) {
            Assert::instanceOf($token, TokenInterface::class);

            $values[] = $token->value;
        }

        Assert::same($values, ['1', '2', '3']);
    }

    public function testParsingWithoutNamedRules(): void
    {
        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')], 'Operand'),
            $parser->addRepetition($parser->addConcatenation([
                $parser->addAlternation([
                    $parser->addTokenReference('T_PLUS')->skip(),
                    $parser->addTokenReference('T_MINUS')->skip(),
                ]),
                $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')], 'Operation'),
            ])),
        ]));

        $compiled = self::createParser(
            lexer: self::createLexer($lexer),
            result: $parser->build($lexer->build()),
        );

        Assert::same(self::collectValues($compiled->parse(StringSource::createFromString('1 + 2 - 3'))), ['1', '2', '3']);
    }

    private static function compile(ParserBuilder $parser): ParserBuilderResult
    {
        return $parser->build(self::createLexerBuilder()->build());
    }
}
