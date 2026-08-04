<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\TokenEmbedding;
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class EmbeddedLexerTest extends TestCase
{
    /**
     * Reads the names written outside of the quotes, while everything between
     * them is read by a lexer of its own.
     *
     * @param iterable<mixed, ChannelInterface> $skip
     */
    private static function createStringLexer(iterable $skip = Lexer::DEFAULT_SKIP_CHANNELS): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('[a-zA-Z_]\w*+', 'T_NAME');
            $lexer->addValue('"', 'T_STRING')->enter('string');

            $string = $lexer->addLexer('string');
            $string->addValue('"', 'T_STRING_END')->exit();
            $string->addPattern('[^"]++', 'T_STRING_CHARS');
        }, $skip);
    }

    /**
     * Reads the fragment up to the closing bracket, the way a lexer written by
     * hand is expected to behave.
     */
    private static function createForeignLexer(): LexerInterface
    {
        return new class implements LexerInterface {
            public function lex(ReadableInterface $source, int $offset = 0): iterable
            {
                $content = $source->content;

                $end = \strpos($content, ']', $offset);
                $end = $end === false ? \strlen($content) : $end;

                $result = [];

                if ($end > $offset) {
                    $result[] = new Token(
                        id: 100,
                        name: 'T_FOREIGN',
                        channel: Channel::Default,
                        value: \substr($content, $offset, $end - $offset),
                        offset: $offset,
                    );
                }

                $result[] = new EndOfInputToken($end);

                return $result;
            }
        };
    }

    /**
     * @param iterable<mixed, ChannelInterface> $skip
     */
    private static function createHostLexer(iterable $skip = Lexer::DEFAULT_SKIP_CHANNELS): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('[a-z]++', 'T_NAME');
            $lexer->addValue(']', 'T_CLOSE');
            $lexer->addValue('[', 'T_OPEN')->enter('fragment');

            $lexer->addEmbeddedLexer('fragment', self::createForeignLexer());
        }, $skip);
    }

    #[TestDox('The tokens of an embedded lexer are carried by the token that entered it')]
    public function testEmbeddedTokensAreCarriedByTheTokenThatEnteredThem(): void
    {
        $lexer = self::createStringLexer(skip: []);

        self::assertSame([
            'T_NAME(name)@0',
            'T_WHITESPACE( )@4',
            'T_STRING(")@5',
            '    T_STRING_CHARS(hello)@6',
            '    T_STRING_END(")@11',
            'EndOfInput()@12',
        ], self::describeTree($lexer->lex(new Source('name "hello"'))));
    }

    #[TestDox('The tokens of an embedded lexer never reach the stream of the lexer that called it')]
    public function testEmbeddedTokensDoNotReachTheStream(): void
    {
        $lexer = self::createStringLexer(skip: []);

        self::assertSame([
            'T_NAME(name)@0',
            'T_WHITESPACE( )@4',
            'T_STRING(")@5',
            'EndOfInput()@12',
        ], self::describe($lexer->lex(new Source('name "hello"'))));
    }

    #[TestDox('The token that entered an embedded lexer spans as far as that lexer has read')]
    public function testEmbeddingSpansTheFragmentItHasRead(): void
    {
        $lexer = self::createStringLexer();
        $source = '"hello"';

        $tokens = \iterator_to_array($lexer->lex(new Source($source)), false);
        $embedding = $tokens[0];

        self::assertInstanceOf(TokenEmbedding::class, $embedding);
        self::assertSame('"', $embedding->value);
        self::assertSame(0, $embedding->offset);
        self::assertSame(7, $embedding->size, 'The size is that of the string rather than of the quote opening it');
    }

    #[TestDox('The tokens read by an embedded lexer are reachable through the token that entered it')]
    public function testEmbeddedTokensAreReachable(): void
    {
        $lexer = self::createStringLexer();

        $tokens = \iterator_to_array($lexer->lex(new Source('"hello"')), false);
        $embedding = $tokens[0];

        self::assertInstanceOf(TokenEmbedding::class, $embedding);
        self::assertCount(2, $embedding);
        self::assertSame('hello', $embedding[0]->value);
        self::assertSame('"', $embedding[1]->value);
        self::assertSame(['hello', '"'], \array_map(
            static fn(mixed $token): string => $token->value,
            \iterator_to_array($embedding, false),
        ));
    }

    #[TestDox('The source is read in full, no matter how much of it an embedded lexer has read')]
    public function testSourceIsCoveredByTheStream(): void
    {
        $lexer = self::createStringLexer(skip: []);
        $source = 'a "b" c';

        self::assertTokensCoverSource($source, $lexer->lex(new Source($source)));
    }

    #[TestDox('A lexer written by hand is embedded the very same way')]
    public function testForeignLexerIsEmbedded(): void
    {
        $lexer = self::createHostLexer(skip: []);

        self::assertSame([
            'T_NAME(a)@0',
            'T_WHITESPACE( )@1',
            'T_OPEN([)@2',
            '    T_FOREIGN(xyz)@3',
            'T_CLOSE(])@6',
            'T_WHITESPACE( )@7',
            'T_NAME(b)@8',
            'EndOfInput()@9',
        ], self::describeTree($lexer->lex(new Source('a [xyz] b'))));
    }

    #[TestDox('The terminal token of an embedded lexer is not carried over')]
    public function testTerminalTokenOfEmbeddedLexerIsNotCarriedOver(): void
    {
        $lexer = self::createHostLexer();
        $source = 'a [xyz] b';

        self::assertTerminatedStream($source, $lexer->lex(new Source($source)));
    }

    #[TestDox('An embedded lexer may be entered several times')]
    public function testEmbeddedLexerMayBeEnteredSeveralTimes(): void
    {
        $lexer = self::createHostLexer();

        $values = [];

        foreach ($lexer->lex(new Source('[one] [two]')) as $token) {
            if ($token instanceof TokenEmbedding) {
                $values[] = $token[0]->value;
            }
        }

        self::assertSame(['one', 'two'], $values);
    }

    #[TestDox('An embedded lexer is not entered again by a source ending with a token that is not reported')]
    public function testEmbeddedLexerIsNotReenteredAfterTheLastReportedToken(): void
    {
        $lexer = self::createStringLexer();

        // The trailing space is read but not reported, so the token that has
        // entered the embedded lexer stays the last one on the list
        $tokens = \iterator_to_array($lexer->lex(new Source('"hello" ')), false);
        $embedding = $tokens[0];

        self::assertInstanceOf(TokenEmbedding::class, $embedding);
        self::assertCount(2, $embedding, 'The embedded lexer is expected to be entered exactly once');
        self::assertSame('hello', $embedding[0]->value);
        self::assertSame(7, $embedding->size);
    }

    #[TestDox('An embedded lexer that never stops still ends the stream')]
    public function testUnterminatedEmbeddedLexerStillEndsTheStream(): void
    {
        $lexer = self::createStringLexer();
        $source = '"hello';

        self::assertTerminatedStream($source, $lexer->lex(new Source($source)));
    }
}
