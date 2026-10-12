<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Exception\EmptyTokenException;
use Phplrt\Lexer\Exception\PcreErrorException;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\Token\TokenEmbedding;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

/**
 * A lexer handing the reading over to another one goes on with what it has
 * already found once the reading is handed back, so the stream it reports must
 * not depend on whether it has gone on or searched the source anew.
 */
#[Group('phplrt/lexer')]
#[Test]
final class ResumableReadingTest extends TestCase
{
    private static function createStringLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('\w++', 'T_WORD');
            $lexer->addValue('"', 'T_QUOTE')->enter('string');

            $string = $lexer->addLexer('string');
            $string->addValue('"', 'T_QUOTE_END')->exit();
            $string->addPattern('[^"]++', 'T_TEXT');
        });
    }

    /**
     * @return list<string>
     */
    private static function read(LexerInterface $lexer, string $source, int $offset = 0): array
    {
        return self::describeTree($lexer->lex(StringSource::createFromString($source), $offset));
    }

    public function testReadingIsHandedBackWhereTheRunHasAMatch(): void
    {
        // The host reads "str" and the closing quote on its own, so the
        // reading is handed back exactly where one of its matches ends
        Assert::same(self::read(self::createStringLexer(), 'word "str" word "str"'), [
            'T_WORD(word)@0',
            'T_QUOTE(")@5',
            '    T_TEXT(str)@6',
            '    T_QUOTE_END(")@9',
            'T_WORD(word)@11',
            'T_QUOTE(")@16',
            '    T_TEXT(str)@17',
            '    T_QUOTE_END(")@20',
            'EndOfInput()@21',
        ]);
    }

    public function testRunStoppedByWhatTheHostCannotReadIsSearchedAnew(): void
    {
        // The host knows nothing about "," and "!", so its run stops inside
        // the string and the rest is searched once the reading is handed back
        Assert::same(self::read(self::createStringLexer(), 'a "b, c!" d "e!" f'), [
            'T_WORD(a)@0',
            'T_QUOTE(")@2',
            '    T_TEXT(b, c!)@3',
            '    T_QUOTE_END(")@8',
            'T_WORD(d)@10',
            'T_QUOTE(")@12',
            '    T_TEXT(e!)@13',
            '    T_QUOTE_END(")@15',
            'T_WORD(f)@17',
            'EndOfInput()@18',
        ]);
    }

    public function testRunWithAMatchAcrossTheHandBackIsSearchedAnew(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addValue('b"c', 'T_ACROSS');
            $lexer->addPattern('\w', 'T_CHAR');
            $lexer->addValue('"', 'T_QUOTE')->enter('string');

            $string = $lexer->addLexer('string');
            $string->addValue('"', 'T_QUOTE_END')->exit();
            $string->addPattern('[^"]++', 'T_TEXT');
        });

        // The host has read 'b"c' as a single token, so the reading handed
        // back right after the quote lands in the middle of it
        Assert::same(self::read($lexer, '"ab"c "d"'), [
            'T_QUOTE(")@0',
            '    T_TEXT(ab)@1',
            '    T_QUOTE_END(")@3',
            'T_CHAR(c)@4',
            'T_QUOTE(")@6',
            '    T_TEXT(d)@7',
            '    T_QUOTE_END(")@8',
            'EndOfInput()@9',
        ]);
    }

    public function testEmbeddedLexerGoesOnWithItsOwnRun(): void
    {
        $source = \str_repeat('word "a b" ', 500);

        $embeddings = 0;

        foreach (self::createStringLexer()->lex(StringSource::createFromString($source)) as $token) {
            if ($token instanceof TokenEmbedding) {
                Assert::same($token[0]->value, 'a b');
                Assert::same($token[1]->offset, $token->offset + 4);

                ++$embeddings;
            }
        }

        Assert::same($embeddings, 500);
    }

    public function testCapturesAreReadOffTheRunThatIsGoneOn(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('(\w)(\w)', 'T_PAIR');
            $lexer->addValue('"', 'T_QUOTE')->enter('string');

            $string = $lexer->addLexer('string');
            $string->addValue('"', 'T_QUOTE_END')->exit();
            $string->addPattern('[^"]++', 'T_TEXT');
        });

        $captures = [];

        foreach ($lexer->lex(StringSource::createFromString('ab "xy" cd "zz" ef')) as $token) {
            if ($token->captures !== []) {
                $captures[] = $token->captures;
            }
        }

        Assert::same($captures, [['a', 'b'], ['c', 'd'], ['e', 'f']]);
    }

    public function testReadingFromAnOffsetMatchesTheRestOfTheWholeReading(): void
    {
        $lexer = self::createStringLexer();
        $source = 'one "two" three "four" five';

        $whole = \iterator_to_array($lexer->lex(StringSource::createFromString($source)), false);

        foreach ($whole as $index => $token) {
            Assert::same(
                self::read($lexer, $source, $token->offset),
                self::describeTree(\array_slice($whole, $index)),
                \sprintf('Reading from offset %d is expected to report the rest of the whole reading', $token->offset),
            );
        }
    }

    public function testReadingIsNotAffectedByThePreviousOne(): void
    {
        $lexer = self::createStringLexer();

        self::read($lexer, 'aaa "b" ccc "d" eee');

        // The same length and the same quotes, so a run kept from the reading
        // above would be gone on with in all the wrong places
        Assert::same(self::read($lexer, 'a "bbb" c "ddd" e'), [
            'T_WORD(a)@0',
            'T_QUOTE(")@2',
            '    T_TEXT(bbb)@3',
            '    T_QUOTE_END(")@6',
            'T_WORD(c)@8',
            'T_QUOTE(")@10',
            '    T_TEXT(ddd)@11',
            '    T_QUOTE_END(")@14',
            'T_WORD(e)@16',
            'EndOfInput()@17',
        ]);
    }

    public function testUnterminatedEmbeddedLexerAfterAHandBackEndsTheStream(): void
    {
        $source = 'a "b" c "d';

        self::assertTerminatedStream($source, self::createStringLexer()->lex(StringSource::createFromString($source)));
    }

    public function testUnknownInputAfterTheHandBackIsReportedInPlace(): void
    {
        $unknown = [];

        foreach (self::createStringLexer()->lex(StringSource::createFromString('a "b" c ! d')) as $token) {
            if ($token->channel === Channel::Unknown) {
                $unknown[] = $token->value . '@' . $token->offset;
            }
        }

        Assert::same($unknown, ['!@8']);
    }

    public function testUnrecognizedInputAfterTheHandBackIsReported(): void
    {
        // Nothing reads a whitespace, not even the unknown token
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\w++', 'T_WORD');
            $lexer->addValue('"', 'T_QUOTE')->enter('string');

            $string = $lexer->addLexer('string');
            $string->addValue('"', 'T_QUOTE_END')->exit();
            $string->addPattern('[^"]++', 'T_TEXT');
        });

        try {
            self::read($lexer, 'a"b"c d');
        } catch (UnrecognizedTokenException $e) {
            Assert::same($e->token->offset, 5);

            return;
        }

        Assert::fail('An unreadable source is expected to be reported');
    }

    public function testUnrecognizedInputInsideALaterEmbeddingIsReported(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\w++', 'T_WORD');
            $lexer->addValue('(', 'T_OPEN')->enter('group');

            $group = $lexer->addLexer('group');
            $group->addValue(')', 'T_CLOSE')->exit();
            $group->addPattern('[a-z]++', 'T_NAME');
        });

        try {
            self::read($lexer, '(a)(b)(c d)');
        } catch (UnrecognizedTokenException $e) {
            Assert::same($e->token->offset, 8);

            return;
        }

        Assert::fail('An unreadable source is expected to be reported');
    }

    public function testEmptyTokenAfterTheHandBackIsReported(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addValue('"', 'T_QUOTE')->enter('string');
            $lexer->addPattern('x*', 'T_X');

            $string = $lexer->addLexer('string');
            $string->addValue('"', 'T_QUOTE_END')->exit();
            $string->addPattern('[^"]++', 'T_TEXT');
        });

        Expect::exception(EmptyTokenException::class);

        self::read($lexer, '"a"y');
    }

    public function testPcreErrorAfterTheHandBackIsReported(): void
    {
        // The limit only holds for a pattern compiled without JIT
        $jit = \ini_set('pcre.jit', '0');
        $limit = \ini_set('pcre.backtrack_limit', '1000');

        try {
            $lexer = self::lexer(static function (LexerBuilder $lexer): void {
                $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
                $lexer->addPattern('(?:x+x+)+y', 'T_CATASTROPHIC');
                $lexer->addPattern('[a-w]', 'T_CHAR');
                $lexer->addValue('"', 'T_QUOTE')->enter('string');

                $string = $lexer->addLexer('string');
                $string->addValue('"', 'T_QUOTE_END')->exit();
                $string->addPattern('[^"]++', 'T_TEXT');
            });

            // The run of the host is stopped by the error, and the embedded
            // lexer searching the source in between leaves no error behind
            Expect::exception(PcreErrorException::class);

            self::read($lexer, '"a" ' . \str_repeat('x', 40));
        } finally {
            \ini_set('pcre.jit', (string) $jit);
            \ini_set('pcre.backtrack_limit', (string) $limit);
        }
    }
}
