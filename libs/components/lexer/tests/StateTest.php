<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Contracts\Lexer\LexerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class StateTest extends TestCase
{
    private static function createInterpolationLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('[a-zA-Z_]\w*+', 'T_NAME');
            $lexer->addValue('"', 'T_STRING_BEGIN')->enter('string');

            $string = $lexer->addState('string');
            $string->addValue('"', 'T_STRING_END')->exit();
            $string->addValue('{$', 'T_INTERPOLATION_BEGIN')->enter('interpolation');
            $string->addPattern('[^"{]++', 'T_STRING_CHARS');

            $interpolation = $lexer->addState('interpolation');
            $interpolation->addValue('}', 'T_INTERPOLATION_END')->exit();
            $interpolation->addPattern('[a-zA-Z_]\w*+', 'T_INTERPOLATION_NAME');
        });
    }

    private static function createNestedCommentsLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('[a-zA-Z_]\w*+', 'T_NAME');
            $lexer->addValue('/*', 'T_COMMENT_BEGIN')->enter('comment');

            $comment = $lexer->addState('comment');
            $comment->addValue('/*', 'T_COMMENT_NESTED')->enter('comment');
            $comment->addValue('*/', 'T_COMMENT_END')->exit();
            $comment->addPattern('[^*/]++', 'T_COMMENT_TEXT');
        });
    }

    #[TestDox('A state is entered on a transition token and left back afterwards')]
    public function testStateIsEnteredAndLeftBack(): void
    {
        $lexer = self::createInterpolationLexer();
        $source = 'name "hello {$user} !"';

        $actual = self::describe($lexer->lex($source));

        self::assertSame([
            'T_NAME(name)@0',
            'T_WHITESPACE( )@4',
            'T_STRING_BEGIN(")@5',
            'T_STRING_CHARS(hello )@6',
            'T_INTERPOLATION_BEGIN({$)@12',
            'T_INTERPOLATION_NAME(user)@14',
            'T_INTERPOLATION_END(})@18',
            'T_STRING_CHARS( !)@19',
            'T_STRING_END(")@21',
            'EndOfInput()@22',
        ], $actual);
    }

    #[TestDox('A state applies only its own token definitions')]
    public function testStateAppliesItsOwnDefinitionsOnly(): void
    {
        $lexer = self::createInterpolationLexer();
        $source = '"hello"';

        $names = [];

        foreach ($lexer->lex($source) as $token) {
            $names[] = $token->name;
        }

        self::assertNotContains('T_NAME', $names);
        self::assertContains('T_STRING_CHARS', $names);
    }

    #[TestDox('A transition token belongs to the state it was matched in, not the one it switches to')]
    public function testTransitionTokenBelongsToTheStateItWasMatchedIn(): void
    {
        $lexer = self::createInterpolationLexer();
        $source = '"a"';

        $names = [];

        foreach ($lexer->lex($source) as $token) {
            $names[] = $token->name;
        }

        self::assertSame(['T_STRING_BEGIN', 'T_STRING_CHARS', 'T_STRING_END', 'EndOfInput'], $names);
    }

    #[TestDox('A state is able to enter itself recursively')]
    public function testStateCanEnterItself(): void
    {
        $lexer = self::createNestedCommentsLexer();
        $source = 'a /* x /* y */ z */ b';

        $actual = self::describe($lexer->lex($source));

        self::assertSame([
            'T_NAME(a)@0',
            'T_WHITESPACE( )@1',
            'T_COMMENT_BEGIN(/*)@2',
            'T_COMMENT_TEXT( x )@4',
            'T_COMMENT_NESTED(/*)@7',
            'T_COMMENT_TEXT( y )@9',
            'T_COMMENT_END(*/)@12',
            'T_COMMENT_TEXT( z )@14',
            'T_COMMENT_END(*/)@17',
            'T_WHITESPACE( )@19',
            'T_NAME(b)@20',
            'EndOfInput()@21',
        ], $actual);
    }

    #[TestDox('A nested state returns to the state it came from')]
    public function testNestedStateReturnsToTheStateItCameFrom(): void
    {
        $lexer = self::createNestedCommentsLexer();
        $source = '/* /* */ */ tail';

        $names = [];

        foreach ($lexer->lex($source) as $token) {
            $names[] = $token->name;
        }

        self::assertContains('T_NAME', $names, 'The initial state is expected to be restored');
    }

    #[TestDox('Token identifiers are unique across all states')]
    public function testTokenIdentifiersAreUniqueAcrossStates(): void
    {
        $lexer = self::createInterpolationLexer();
        $source = 'name "hello {$user} !"';

        $identifiers = [];

        foreach ($lexer->lex($source) as $token) {
            $name = $token->name ?? 'eoi';

            if (isset($identifiers[$name])) {
                self::assertSame($identifiers[$name], $token->id);

                continue;
            }

            $identifiers[$name] = $token->id;
        }

        self::assertSame(
            \count($identifiers),
            \count(\array_unique($identifiers)),
        );
    }

    #[TestDox('Tokens produced by every state describe the source completely')]
    public function testTokensOfEveryStateDescribeTheSource(): void
    {
        $lexer = self::createInterpolationLexer();
        $source = 'name "hello {$user} !" tail';

        self::assertTokensMatchSource($source, $lexer->lex($source));
        self::assertTokensCoverSource($source, $lexer->lex($source));
    }

    #[TestDox('An unterminated state still produces a terminated stream')]
    public function testUnterminatedStateStillProducesTerminatedStream(): void
    {
        $lexer = self::createInterpolationLexer();
        $source = 'name "unterminated';

        self::assertTerminatedStream($source, $lexer->lex($source));
    }
}
