<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Builder\ParserBuilderResult;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Context;
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class OptimizationTest extends TestCase
{
    #[TestDox('An alternation of a single rule is replaced by that rule')]
    public function testSingleAlternative(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addAlternation([$parser->addTokenReference('T_NUMBER')]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        self::assertSame([
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('A concatenation of a single rule is joined with the rule above')]
    public function testSingleConcatenation(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        self::assertSame([
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('A concatenation whose value is not joined with another one is left as is')]
    public function testStandaloneConcatenation(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addAlternation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')]),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        self::assertSame([
            '0: Alternation(1, 3)',
            '1: Concatenation(2)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('The nested concatenations are joined into one')]
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

        self::assertSame([
            '0: Concatenation(1, 2, 3)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
            '3: Lexeme(3, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('A nested alternation is left as is, since it is skipped along with every rule of it')]
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

        self::assertSame([
            '0: Alternation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Alternation(3, 4)',
            '3: Lexeme(2, skip)',
            '4: Lexeme(3, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('A concatenation referred to more than once is left as is')]
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

        self::assertSame([
            '0: Concatenation(1, 4, 1)',
            '1: Concatenation(2, 3)',
            '2: Lexeme(2, skip)',
            '3: Lexeme(3, skip)',
            '4: Lexeme(1, keep)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('The rules recognizing the same input are merged into one')]
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

        self::assertSame([
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Repetition(3, 0, INF)',
            '3: Concatenation(4, 1)',
            '4: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('The rules recognizing the same token in a different way are left as is')]
    public function testDifferentOccurrences(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addTokenReference('T_NUMBER')->skip(),
        ]));

        self::assertSame([
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(1, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('A repetition of a single occurrence is joined with the rule above')]
    public function testSingleRepetition(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRepetition($parser->addTokenReference('T_NUMBER'), max: 1, min: 1),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        self::assertSame([
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('An optional rule that is always recognized is replaced by that rule')]
    public function testOptionalOfAnAlwaysMatchingRule(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addOptional($parser->addRepetition($parser->addTokenReference('T_NUMBER'))),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        self::assertSame([
            '0: Concatenation(1, 3)',
            '1: Repetition(2, 0, INF)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('An optional rule that may fail is left as is')]
    public function testOptionalOfAFailingRule(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addOptional($parser->addRepetition($parser->addTokenReference('T_NUMBER'), min: 1)),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        self::assertSame([
            '0: Concatenation(1, 4)',
            '1: Optional(2)',
            '2: Repetition(3, 1, INF)',
            '3: Lexeme(1, keep)',
            '4: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('A repetition of a repetition is joined into one')]
    public function testNestedRepetition(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRepetition(
                $parser->addRepetition($parser->addTokenReference('T_NUMBER'), min: 1),
            ),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        self::assertSame([
            '0: Concatenation(1, 3)',
            '1: Repetition(2, 0, INF)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('A repetition demanding several occurrences is left as is')]
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

        self::assertSame([
            '0: Concatenation(1, 4)',
            '1: Repetition(2, 2, INF)',
            '2: Repetition(3, 1, INF)',
            '3: Lexeme(1, keep)',
            '4: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('An alternative repeating an earlier one is removed')]
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

        self::assertSame([
            '0: Concatenation(1, 4)',
            '1: Alternation(2, 3)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(3, skip)',
            '4: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('The alternatives recognizing the same input are told apart before being removed')]
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

        self::assertSame([
            '0: Concatenation(1, 4)',
            '1: Alternation(2, 3)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(3, skip)',
            '4: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('A named rule is removed as well, since the analysis reaches nothing by a name')]
    public function testNamedRule(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')], 'Group'),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        $result = self::compile($parser);

        self::assertSame([
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ], self::describe($result));
        self::assertSame([], $result->constants);
    }

    #[TestDox('The name of a rule building a node of its own is kept')]
    public function testNamedRuleWithReducer(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')], 'Group')
                ->setReducer(static fn(Context $context, mixed $children): mixed => $children),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        $result = self::compile($parser);

        self::assertSame([
            '0: Concatenation(1, 3)',
            '1: Concatenation(2)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ], self::describe($result));
        self::assertSame(['Group' => 1], $result->constants);
    }

    #[TestDox('A rule whose value is joined further up is removed as well')]
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

        self::assertSame([
            '0: Concatenation(1, 4)',
            '1: Alternation(2, 3)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(3, skip)',
            '4: Lexeme(2, skip)',
        ], self::describe($result));
        self::assertSame([], $result->constants);
    }

    #[TestDox('A rule whose value is given to a reducer is left as is')]
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

        self::assertSame([
            '0: Concatenation(1, 5)',
            '1: Alternation(2, 4)',
            '2: Concatenation(3)',
            '3: Lexeme(1, keep)',
            '4: Lexeme(3, skip)',
            '5: Lexeme(2, skip)',
        ], self::describe($result));
        self::assertSame(['Number' => 2], $result->constants);
    }

    #[TestDox('A rule building a node of its own is left as is')]
    public function testRuleWithReducer(): void
    {
        $parser = new ParserBuilder();

        $group = $parser->addConcatenation([$parser->addTokenReference('T_NUMBER')])
            ->setReducer(static fn(Context $context, mixed $children): mixed => $children);

        $parser->setInitialRule($parser->addConcatenation([
            $group,
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        self::assertSame([
            '0: Concatenation(1, 3)',
            '1: Concatenation(2)',
            '2: Lexeme(1, keep)',
            '3: Lexeme(2, skip)',
        ], self::describe(self::compile($parser)));
    }

    #[TestDox('The optimized grammar is the same as the one defined without the extra rules')]
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

        self::assertSame(
            self::describe(self::compile($plain)),
            self::describe(self::compile($verbose)),
        );
    }

    #[TestDox('The optimized parser recognizes the source')]
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

        $actual = $compiled->parse(new Source('1 + 2 - 3'));

        self::assertIsList($actual);

        $values = [];

        foreach ($actual as $token) {
            self::assertInstanceOf(TokenInterface::class, $token);

            $values[] = $token->value;
        }

        self::assertSame(['1', '2', '3'], $values);
    }

    #[TestDox('A parser with the named rules removed recognizes the source the same way')]
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

        self::assertSame(['1', '2', '3'], self::collectValues($compiled->parse(new Source('1 + 2 - 3'))));
    }

    private static function compile(ParserBuilder $parser): ParserBuilderResult
    {
        return $parser->build(self::createLexerBuilder()->build());
    }
}
