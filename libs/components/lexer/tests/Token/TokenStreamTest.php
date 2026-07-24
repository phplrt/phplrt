<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Token;

use Phplrt\Compiler\Lexer\LexerBuilder;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class TokenStreamTest extends TestCase
{
    private static function createExpressionLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->match('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->match('\d++', 'T_NUMBER');
            $lexer->match('[a-zA-Z_]\w*+', 'T_NAME');
            $lexer->value('+', 'T_PLUS');
            $lexer->value('=', 'T_ASSIGN');
        });
    }

    #[TestDox('Tokens are produced in the same order they occur in the source')]
    public function testProducesTokensInSourceOrder(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'x = 1 + 20';

        $actual = self::describe($lexer->lex($source));

        self::assertSame([
            'T_NAME(x)@0',
            'T_WHITESPACE( )@1',
            'T_ASSIGN(=)@2',
            'T_WHITESPACE( )@3',
            'T_NUMBER(1)@4',
            'T_WHITESPACE( )@5',
            'T_PLUS(+)@6',
            'T_WHITESPACE( )@7',
            'T_NUMBER(20)@8',
            'eoi()@10',
        ], $actual);
    }

    #[TestDox('Every token points at the exact place its lexeme was read from')]
    public function testEveryTokenPointsAtItsPositionInSource(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'first = 42 + second';

        self::assertTokensMatchSource($source, $lexer->lex($source));
    }

    #[TestDox('The tokens together cover the whole source')]
    public function testTokensCoverTheWholeSource(): void
    {
        $lexer = self::createExpressionLexer();
        $source = '  alpha =  1+2  ';

        self::assertTokensCoverSource($source, $lexer->lex($source));
    }

    #[TestDox('The stream is always terminated by a single end of input token')]
    public function testStreamIsTerminatedByEndOfInputToken(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'a = 1';

        self::assertTerminatedStream($source, $lexer->lex($source));
    }

    #[TestDox('An empty source produces nothing but the terminal token')]
    public function testEmptySourceProducesOnlyTheTerminalToken(): void
    {
        $lexer = self::createExpressionLexer();

        $tokens = \iterator_to_array($lexer->lex(''), false);

        self::assertCount(1, $tokens);
        self::assertSame(Channel::EndOfInput, $tokens[0]->channel);
        self::assertSame(0, $tokens[0]->offset);
    }

    #[TestDox('A token value holds the exact lexeme, whitespace included')]
    public function testValueContainsTheExactLexeme(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->match('"[^"]*+"', 'T_STRING');
        });
        $source = '"  spaced  "';

        $tokens = \iterator_to_array($lexer->lex($source), false);

        self::assertSame('"  spaced  "', $tokens[0]->value);
    }

    #[TestDox('Token offsets are measured in bytes, not in characters')]
    public function testOffsetsAreMeasuredInBytes(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->match('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->match('\p{L}++', 'T_WORD');
        });
        $source = 'привет мир';

        $actual = self::describe($lexer->lex($source));

        self::assertSame([
            'T_WORD(привет)@0',
            'T_WHITESPACE( )@12',
            'T_WORD(мир)@13',
            'eoi()@19',
        ], $actual);
    }

    #[TestDox('An anonymous token has no name')]
    public function testAnonymousTokenHasNoName(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->match('\d++');
        });

        $tokens = \iterator_to_array($lexer->lex('42'), false);

        self::assertNull($tokens[0]->name);
    }

    #[TestDox('Tokens of different types are distinguished by their identifier')]
    public function testNamedTokensAreDistinguishedByIdentifier(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'x = 1';

        $identifiers = [];

        foreach ($lexer->lex($source) as $token) {
            $identifiers[$token->name ?? 'eoi'] = $token->id;
        }

        self::assertSame(
            \count($identifiers),
            \count(\array_unique($identifiers)),
        );
    }

    #[TestDox('Analysing the same source twice produces the same result')]
    public function testRepeatedAnalysisProducesTheSameResult(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'a = 1 + 2';

        self::assertSame(
            self::describe($lexer->lex($source)),
            self::describe($lexer->lex($source)),
        );
    }

    #[TestDox('An analysis does not depend on the previous ones')]
    public function testAnalysisDoesNotDependOnPreviousCalls(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'a = 1';

        $before = self::describe($lexer->lex($source));
        $lexer->lex('completely + different + source');
        $after = self::describe($lexer->lex($source));

        self::assertSame($before, $after);
    }
}
