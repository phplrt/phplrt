<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer-compiler')]
final class BuildingContextTest extends TestCase
{
    #[TestDox('A pass rewriting the lexers does not affect the builder it has been called on')]
    public function testStatesAreIsolated(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('"')->enter('string');
        $lexer->addLexer('string')->addPattern('[^"]++')->exit();

        $lexer->addCompilerPass(new class implements LexerCompilerPassInterface {
            public function process(LexerBuildingContext $context): void
            {
                $context->lexers['unused'] = $context->lexers['string'];
                $context->tokens[] = new RegexTokenDefinition('\d++');
            }
        });

        $lexer->build();

        self::assertCount(1, $lexer->tokens);
        self::assertSame(['string'], \array_keys($lexer->lexers));
    }

    #[TestDox('A pass removing the lexers does not affect the builder it has been called on')]
    public function testRemovedStatesAreIsolated(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('"')->enter('string');
        $lexer->addLexer('string')->addPattern('[^"]++')->exit();

        $lexer->addCompilerPass(new class implements LexerCompilerPassInterface {
            public function process(LexerBuildingContext $context): void
            {
                unset($context->lexers['string']);
            }
        }, LexerBuilder::PASS_PRIORITY_OPTIMIZE);

        $result = $lexer->build();

        self::assertSame([], \array_keys($result->lexers), 'The state has been dropped from the result');
        self::assertSame(['string'], \array_keys($lexer->lexers), 'The state is still defined by the builder');
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
