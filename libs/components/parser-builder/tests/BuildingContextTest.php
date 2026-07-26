<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Compiler\ParserBuildingContext;
use Phplrt\Parser\Builder\Compiler\ParserCompilerPassInterface;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\ParserBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class BuildingContextTest extends TestCase
{
    #[TestDox('An optimized grammar does not affect the rules the builder has been given')]
    public function testRulesAreIsolated(): void
    {
        $parser = new ParserBuilder();

        $nested = $parser->addConcatenation([
            $parser->addTokenReference('T_PLUS')->skip(),
            $parser->addTokenReference('T_NUMBER'),
        ]);

        $initial = $parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $nested,
        ]);

        $parser->setInitialRule($initial);

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertSame([
            '0: Concatenation(1, 2, 1)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ], self::describe($result), 'The nested concatenation has been joined');

        self::assertCount(2, $initial->rules, 'The rules of the builder are left as they have been defined');
        self::assertSame($nested, $initial->rules[1]);
    }

    #[TestDox('The very same builder compiles into the very same parser every time')]
    public function testBuildIsRepeatable(): void
    {
        $parser = new ParserBuilder();

        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRuleReference('Number'),
            $parser->addRepetition($parser->addConcatenation([
                $parser->addTokenReference('T_PLUS')->skip(),
                $parser->addRuleReference('Number'),
            ])),
        ]));

        $parser->addTokenReference('T_NUMBER', 'Number');

        $lexer = self::createLexerBuilder()->build();

        self::assertSame(
            self::describe($parser->build($lexer)),
            self::describe($parser->build($lexer)),
        );
    }

    #[TestDox('A rule spliced out of the grammar is no longer compiled into the parser')]
    public function testDetachedRuleIsNotCompiled(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addTokenReference('T_PLUS')->skip(),
        ]));

        $parser->addCompilerPass(new class implements ParserCompilerPassInterface {
            public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
            {
                $initial = $context->initial;

                if (!$initial instanceof ConcatenationRuleDefinition) {
                    return;
                }

                $initial->setRules([$initial->rules[0]]);

                $context->rules = $initial->collectRules();
            }
        }, ParserBuilder::PASS_PRIORITY_CHECK - 1);

        $result = $parser->build(self::createLexerBuilder()->build());

        self::assertSame([
            '0: Concatenation(1)',
            '1: Lexeme(1, keep)',
        ], self::describe($result));
    }
}
