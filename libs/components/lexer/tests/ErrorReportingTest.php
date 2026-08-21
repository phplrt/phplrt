<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class ErrorReportingTest extends TestCase
{
    private static function createIncompleteLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('[a-z]++', 'T_NAME');
        });
    }

    #[TestDox('An unreadable source is reported instead of being silently truncated')]
    public function testUnreadableSourceIsReported(): void
    {
        $lexer = self::createIncompleteLexer();

        $this->expectException(RuntimeExceptionInterface::class);

        \iterator_to_array($lexer->lex(StringSource::createFromString('first second')), false);
    }

    #[TestDox('A reported error is a lexer exception')]
    public function testReportedExceptionIsALexerException(): void
    {
        $lexer = self::createIncompleteLexer();

        $this->expectException(LexerExceptionInterface::class);

        \iterator_to_array($lexer->lex(StringSource::createFromString('first second')), false);
    }

    #[TestDox('A reported error points at the unreadable fragment')]
    public function testReportedTokenPointsAtTheUnreadableFragment(): void
    {
        $lexer = self::createIncompleteLexer();

        try {
            \iterator_to_array($lexer->lex(StringSource::createFromString('first second')), false);
        } catch (RuntimeExceptionInterface $e) {
            self::assertSame(5, $e->token->offset);

            return;
        }

        self::fail('An unreadable source is expected to be reported');
    }

    #[TestDox('A completely readable source is not reported as an error')]
    public function testReadableSourceIsNotReported(): void
    {
        $lexer = self::createIncompleteLexer();

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString('word')), false);

        self::assertCount(2, $tokens);
    }

    #[TestDox('A fragment covered by the implicit unknown token does not truncate the analysis')]
    public function testAnalysisIsNotSilentlyTruncated(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('[a-z]++', 'T_NAME');
        }, skip: []);
        $source = 'abc 123 def';

        self::assertTokensCoverSource($source, $lexer->lex(StringSource::createFromString($source)));
    }

    #[TestDox('A failure that happens inside a state is reported as well')]
    public function testFailureInsideAStateIsReportedToo(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('[a-z]++', 'T_NAME');
            $lexer->addValue('"', 'T_STRING_BEGIN')->enter('string');

            $string = $lexer->addLexer('string');
            $string->addValue('"', 'T_STRING_END')->exit();
            $string->addPattern('[a-z]++', 'T_STRING_CHARS');
        });

        $this->expectException(RuntimeExceptionInterface::class);

        \iterator_to_array($lexer->lex(StringSource::createFromString('"abc def"')), false);
    }
}
