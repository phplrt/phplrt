<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

/**
 * A hidden token never reaches the stream, so the reading is never handed
 * over by it.
 */
#[Group('phplrt/lexer-builder')]
#[Test]
final class HiddenTransitionTest extends TestCase
{
    private static function createBuilder(): LexerBuilder
    {
        $builder = new LexerBuilder();
        $builder->addPattern('\w++', 'T_WORD');

        $comment = $builder->addLexer('comment');
        $comment->addValue('*/', 'T_COMMENT_END')->exit();
        $comment->addPattern('(?:[^*]|\*(?!/))++', 'T_COMMENT_TEXT');

        return $builder;
    }

    public function testHiddenTokenEnteringALexerIsReported(): void
    {
        $builder = self::createBuilder();
        $builder->addValue('/*', 'T_COMMENT')->setHidden()->enter('comment');

        Expect::exception(CompilationFailedException::class)
            ->withMessageContaining('"/*" (T_COMMENT) is hidden, so it cannot hand the reading over to the "comment" lexer');

        $builder->build();
    }

    public function testHiddenTokenEndingTheReadingIsReported(): void
    {
        $builder = new LexerBuilder();
        $builder->addValue('"', 'T_QUOTE')->enter('string');

        $string = $builder->addLexer('string');
        $string->addValue('"', 'T_QUOTE_END')->setHidden()->exit();
        $string->addPattern('[^"]++', 'T_TEXT');

        Expect::exception(CompilationFailedException::class)
            ->withMessageContaining('(T_QUOTE_END) is hidden, so it cannot end the reading');

        $builder->build();
    }

    public function testTokenOfAChannelOfItsOwnEntersALexer(): void
    {
        $builder = self::createBuilder();
        $builder->addValue('/*', 'T_COMMENT')->setChannel('comment')->enter('comment');

        Assert::same(\array_values($builder->build()->names), ['T_WORD', 'T_COMMENT']);
    }

    public function testHiddenTokenWithoutATransitionIsBuilt(): void
    {
        $builder = new LexerBuilder();
        $builder->addPattern('\w++', 'T_WORD');
        $builder->addPattern('/\*.*?\*/', 'T_COMMENT')->setHidden();

        Assert::same(\array_values($builder->build()->names), ['T_WORD', 'T_COMMENT']);
    }
}
