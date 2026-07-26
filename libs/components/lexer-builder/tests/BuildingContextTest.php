<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\ValueTokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer-compiler')]
final class BuildingContextTest extends TestCase
{
    #[TestDox('A pass rewriting the states does not affect the builder it has been called on')]
    public function testStatesAreIsolated(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('"')->enter('string');
        $lexer->addState('string')->addPattern('[^"]++');

        $lexer->addCompilerPass(new class implements LexerCompilerPassInterface {
            public function process(LexerBuildingContext $context): void
            {
                $context->states['string'][] = new ValueTokenDefinition('"');
                $context->tokens[] = new RegexTokenDefinition('\d++');
            }
        });

        $lexer->build();

        self::assertCount(1, $lexer->tokens);
        self::assertCount(1, $lexer->addState('string')->tokens);
    }

    #[TestDox('A pass removing the states does not affect the builder it has been called on')]
    public function testRemovedStatesAreIsolated(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('"')->enter('string');
        $lexer->addState('string')->addPattern('[^"]++')->exit();

        $lexer->addCompilerPass(new class implements LexerCompilerPassInterface {
            public function process(LexerBuildingContext $context): void
            {
                unset($context->states['string']);
            }
        }, LexerBuilder::PASS_PRIORITY_OPTIMIZE);

        $result = $lexer->build();

        self::assertSame([], \array_keys($result->states), 'The state has been dropped from the result');
        self::assertSame(['string'], \array_keys($lexer->states), 'The state is still defined by the builder');
    }

    #[TestDox('The token definitions are shared with the builder, so that a parser may refer to them')]
    public function testTokenDefinitionsAreShared(): void
    {
        $lexer = new LexerBuilder();
        $number = $lexer->addPattern('\d++');

        $result = $lexer->build();

        self::assertSame(0, $result->findTokenId($number));
    }
}
