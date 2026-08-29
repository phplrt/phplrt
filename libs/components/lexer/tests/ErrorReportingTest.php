<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer')]
#[Test]
final class ErrorReportingTest extends TestCase
{
    private static function createIncompleteLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('[a-z]++', 'T_NAME');
        });
    }

    public function testUnreadableSourceIsReported(): void
    {
        $lexer = self::createIncompleteLexer();

        Expect::exception(RuntimeExceptionInterface::class);

        \iterator_to_array($lexer->lex(StringSource::createFromString('first second')), false);
    }

    public function testReportedExceptionIsALexerException(): void
    {
        $lexer = self::createIncompleteLexer();

        Expect::exception(LexerExceptionInterface::class);

        \iterator_to_array($lexer->lex(StringSource::createFromString('first second')), false);
    }

    public function testReportedTokenPointsAtTheUnreadableFragment(): void
    {
        $lexer = self::createIncompleteLexer();

        try {
            \iterator_to_array($lexer->lex(StringSource::createFromString('first second')), false);
        } catch (RuntimeExceptionInterface $e) {
            Assert::same($e->token->offset, 5);

            return;
        }

        Assert::fail('An unreadable source is expected to be reported');
    }

    public function testReadableSourceIsNotReported(): void
    {
        $lexer = self::createIncompleteLexer();

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString('word')), false);

        Assert::count($tokens, 2);
    }

    public function testAnalysisIsNotSilentlyTruncated(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('[a-z]++', 'T_NAME');
        }, skip: []);
        $source = 'abc 123 def';

        self::assertTokensCoverSource($source, $lexer->lex(StringSource::createFromString($source)));
    }

    public function testFailureInsideAStateIsReportedToo(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('[a-z]++', 'T_NAME');
            $lexer->addValue('"', 'T_STRING_BEGIN')->enter('string');

            $string = $lexer->addLexer('string');
            $string->addValue('"', 'T_STRING_END')->exit();
            $string->addPattern('[a-z]++', 'T_STRING_CHARS');
        });

        Expect::exception(RuntimeExceptionInterface::class);

        \iterator_to_array($lexer->lex(StringSource::createFromString('"abc def"')), false);
    }
}
