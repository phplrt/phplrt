<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
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

    #[TestDox('The analysis starts at the given offset')]
    public function testAnalysisStartsAtTheGivenOffset(): void
    {
        $lexer = self::createWordsLexer();
        $source = 'one two three';

        $actual = self::describe($lexer->lex(new Source($source), 8));

        self::assertSame([
            'T_NAME(three)@8',
            'EndOfInput()@13',
        ], $actual);
    }

    #[TestDox('Token offsets stay absolute to the whole source, not to the starting offset')]
    public function testOffsetsRemainAbsoluteToTheWholeSource(): void
    {
        $lexer = self::createWordsLexer();
        $source = 'one two three';

        self::assertTokensMatchSource($source, $lexer->lex(new Source($source), 4));
    }

    #[TestDox('An offset equal to the source length produces only the terminal token')]
    public function testOffsetEqualToSourceLengthProducesOnlyTheTerminalToken(): void
    {
        $lexer = self::createWordsLexer();
        $source = 'one two';

        $tokens = \iterator_to_array($lexer->lex(new Source($source), \strlen($source)), false);

        self::assertCount(1, $tokens);
        self::assertSame(Channel::EndOfInput, $tokens[0]->channel);
        self::assertSame(\strlen($source), $tokens[0]->offset);
    }

    #[TestDox('The default offset is the beginning of the source')]
    public function testZeroOffsetIsTheDefault(): void
    {
        $lexer = self::createWordsLexer();
        $source = 'one two';

        self::assertSame(
            self::describe($lexer->lex(new Source($source))),
            self::describe($lexer->lex(new Source($source), 0)),
        );
    }

    #[TestDox('A negative offset is rejected')]
    public function testNegativeOffsetIsRejected(): void
    {
        $lexer = self::createWordsLexer();

        $this->expectException(\InvalidArgumentException::class);

        \iterator_to_array($lexer->lex(new Source('one two'), -1), false);
    }
}
