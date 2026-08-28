<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer-compiler')]
#[Test]
final class BuildingContextTest extends TestCase
{
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

        Assert::count($lexer->tokens, 1);
        Assert::same(\array_keys($lexer->lexers), ['string']);
    }

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

        Assert::same(\array_keys($result->lexers), [], 'The state has been dropped from the result');
        Assert::same(\array_keys($lexer->lexers), ['string'], 'The state is still defined by the builder');
    }

    public function testTokenDefinitionsAreShared(): void
    {
        $lexer = new LexerBuilder();
        $number = $lexer->addPattern('\d++');

        $result = $lexer->build();

        Assert::same($result->findTokenId($number), 0);
    }
}
