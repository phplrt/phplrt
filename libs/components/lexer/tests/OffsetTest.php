<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer')]
#[Test]
final class OffsetTest extends TestCase
{
    private static function createWordsLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('\d++', 'T_NUMBER');
            $lexer->addPattern('[a-zA-Z_]\w*+', 'T_NAME');
        });
    }

    public function testAnalysisStartsAtTheGivenOffset(): void
    {
        $lexer = self::createWordsLexer();
        $source = 'one two three';

        $actual = self::describe($lexer->lex(StringSource::createFromString($source), 8));

        Assert::same($actual, [
            'T_NAME(three)@8',
            'EndOfInput()@13',
        ]);
    }

    public function testOffsetsRemainAbsoluteToTheWholeSource(): void
    {
        $lexer = self::createWordsLexer();
        $source = 'one two three';

        self::assertTokensMatchSource($source, $lexer->lex(StringSource::createFromString($source), 4));
    }

    public function testOffsetsRemainAbsoluteAfterThePartialReadingOfTheSource(): void
    {
        $lexer = self::createWordsLexer();
        $source = StringSource::createFromString('one two three');

        $source->read(0, 4);

        Assert::same(self::describe($lexer->lex($source)), [
            'T_NAME(one)@0',
            'T_NAME(two)@4',
            'T_NAME(three)@8',
            'EndOfInput()@13',
        ]);
    }

    public function testOffsetEqualToSourceLengthProducesOnlyTheTerminalToken(): void
    {
        $lexer = self::createWordsLexer();
        $source = 'one two';

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString($source), \strlen($source)), false);

        Assert::count($tokens, 1);
        Assert::same($tokens[0]->channel, Channel::EndOfInput);
        Assert::same($tokens[0]->offset, \strlen($source));
    }

    public function testZeroOffsetIsTheDefault(): void
    {
        $lexer = self::createWordsLexer();
        $source = 'one two';

        Assert::same(self::describe($lexer->lex(StringSource::createFromString($source), 0)), self::describe($lexer->lex(StringSource::createFromString($source))));
    }

    public function testNegativeOffsetIsRejected(): void
    {
        $lexer = self::createWordsLexer();

        Expect::exception(\InvalidArgumentException::class);

        \iterator_to_array($lexer->lex(StringSource::createFromString('one two'), -1), false);
    }
}
