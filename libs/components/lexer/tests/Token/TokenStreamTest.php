<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Token;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Tests\TestCase;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer')]
#[Test]
final class TokenStreamTest extends TestCase
{
    private static function createExpressionLexer(iterable $skip = Lexer::DEFAULT_SKIP_CHANNELS): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('\d++', 'T_NUMBER');
            $lexer->addPattern('[a-zA-Z_]\w*+', 'T_NAME');
            $lexer->addValue('+', 'T_PLUS');
            $lexer->addValue('=', 'T_ASSIGN');
        }, $skip);
    }

    public function testProducesTokensInSourceOrder(): void
    {
        $lexer = self::createExpressionLexer(skip: []);
        $source = 'x = 1 + 20';

        $actual = self::describe($lexer->lex(StringSource::createFromString($source)));

        Assert::same($actual, [
            'T_NAME(x)@0',
            'T_WHITESPACE( )@1',
            'T_ASSIGN(=)@2',
            'T_WHITESPACE( )@3',
            'T_NUMBER(1)@4',
            'T_WHITESPACE( )@5',
            'T_PLUS(+)@6',
            'T_WHITESPACE( )@7',
            'T_NUMBER(20)@8',
            'EndOfInput()@10',
        ]);
    }

    public function testSkippedChannelDoesNotReachTheStream(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'x = 1 + 20';

        $actual = self::describe($lexer->lex(StringSource::createFromString($source)));

        Assert::same($actual, [
            'T_NAME(x)@0',
            'T_ASSIGN(=)@2',
            'T_NUMBER(1)@4',
            'T_PLUS(+)@6',
            'T_NUMBER(20)@8',
            'EndOfInput()@10',
        ]);
    }

    public function testEveryTokenPointsAtItsPositionInSource(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'first = 42 + second';

        self::assertTokensMatchSource($source, $lexer->lex(StringSource::createFromString($source)));
    }

    public function testTokensCoverTheWholeSource(): void
    {
        $lexer = self::createExpressionLexer(skip: []);
        $source = '  alpha =  1+2  ';

        self::assertTokensCoverSource($source, $lexer->lex(StringSource::createFromString($source)));
    }

    public function testStreamIsTerminatedByEndOfInputToken(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'a = 1';

        self::assertTerminatedStream($source, $lexer->lex(StringSource::createFromString($source)));
    }

    public function testEmptySourceProducesOnlyTheTerminalToken(): void
    {
        $lexer = self::createExpressionLexer();

        $tokens = \iterator_to_array($lexer->lex(StringSource::createEmpty()), false);

        Assert::count($tokens, 1);
        Assert::same($tokens[0]->channel, Channel::EndOfInput);
        Assert::same($tokens[0]->offset, 0);
    }

    public function testValueContainsTheExactLexeme(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('"[^"]*+"', 'T_STRING');
        });
        $source = '"  spaced  "';

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString($source)), false);

        Assert::same($tokens[0]->value, '"  spaced  "');
    }

    public function testOffsetsAreMeasuredInBytes(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('\p{L}++', 'T_WORD');
        }, skip: []);
        $source = 'привет мир';

        $actual = self::describe($lexer->lex(StringSource::createFromString($source)));

        Assert::same($actual, [
            'T_WORD(привет)@0',
            'T_WHITESPACE( )@12',
            'T_WORD(мир)@13',
            'EndOfInput()@19',
        ]);
    }

    public function testAnonymousTokenHasNoName(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\d++');
        });

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString('42')), false);

        Assert::null($tokens[0]->name);
    }

    public function testNamedTokensAreDistinguishedByIdentifier(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'x = 1';

        $identifiers = [];

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            $identifiers[$token->name ?? 'eoi'] = $token->id;
        }

        Assert::same(\count(\array_unique($identifiers)), \count($identifiers));
    }

    public function testRepeatedAnalysisProducesTheSameResult(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'a = 1 + 2';

        Assert::same(self::describe($lexer->lex(StringSource::createFromString($source))), self::describe($lexer->lex(StringSource::createFromString($source))));
    }

    public function testAnalysisDoesNotDependOnPreviousCalls(): void
    {
        $lexer = self::createExpressionLexer();
        $source = 'a = 1';

        $before = self::describe($lexer->lex(StringSource::createFromString($source)));
        $lexer->lex(StringSource::createFromString('completely + different + source'));
        $after = self::describe($lexer->lex(StringSource::createFromString($source)));

        Assert::same($after, $before);
    }
}
