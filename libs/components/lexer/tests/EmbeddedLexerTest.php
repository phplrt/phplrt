<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Regex\MarkersRegexGenerator;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class EmbeddedLexerTest extends TestCase
{
    private static function createForeignLexer(): LexerInterface
    {
        return new class () implements LexerInterface {
            public function lex(string $source, int $offset = 0): iterable
            {
                $end = \strpos($source, ']', $offset);
                $end = $end === false ? \strlen($source) : $end;

                $result = [];

                if ($end > $offset) {
                    $result[] = new Token(
                        id: 100,
                        name: 'T_FOREIGN',
                        channel: Channel::Default,
                        value: \substr($source, $offset, $end - $offset),
                        offset: $offset,
                    );
                }

                $result[] = new EndOfInputToken($end);

                return $result;
            }
        };
    }

    private static function createHostLexer(): LexerInterface
    {
        $result = new MarkersRegexGenerator()->generate(
            tokens: [
                new RegexTokenDefinition('\[', 'T_OPEN')->enter('embedded'),
                new RegexTokenDefinition('\]', 'T_CLOSE'),
                new RegexTokenDefinition('\s++', 'T_WHITESPACE')->setHidden(),
                new RegexTokenDefinition('[a-z]++', 'T_NAME'),
            ],
            flags: [
                RegexModifier::Compiled,
                RegexModifier::DotAll,
                RegexModifier::Utf8,
                RegexModifier::Multiline,
            ],
        );

        return new Lexer(
            pattern: $result->pattern,
            channels: $result->channels,
            names: $result->names,
            transitions: $result->transitions,
            states: ['embedded' => self::createForeignLexer()],
        );
    }

    #[TestDox('Tokens read by an embedded lexer are inlined into the host stream')]
    public function testForeignTokensAreInlinedIntoTheStream(): void
    {
        $lexer = self::createHostLexer();
        $source = 'a [xyz] b';

        $actual = self::describe($lexer->lex($source));

        self::assertSame([
            'T_NAME(a)@0',
            'T_WHITESPACE( )@1',
            'T_OPEN([)@2',
            'T_FOREIGN(xyz)@3',
            'T_CLOSE(])@6',
            'T_WHITESPACE( )@7',
            'T_NAME(b)@8',
            'eoi()@9',
        ], $actual);
    }

    #[TestDox('The terminal token of an embedded lexer is not leaked into the host stream')]
    public function testTerminalTokenOfForeignLexerIsNotLeaked(): void
    {
        $lexer = self::createHostLexer();
        $source = 'a [xyz] b';

        self::assertTerminatedStream($source, $lexer->lex($source));
    }

    #[TestDox('Control is returned to the host grammar once the embedded lexer stops')]
    public function testControlIsReturnedToTheHostGrammar(): void
    {
        $lexer = self::createHostLexer();
        $source = 'a [xyz] b';

        self::assertTokensCoverSource($source, $lexer->lex($source));
    }

    #[TestDox('An embedded state may be entered several times')]
    public function testForeignStateMayBeEnteredSeveralTimes(): void
    {
        $lexer = self::createHostLexer();
        $source = '[one] [two]';

        $names = [];

        foreach ($lexer->lex($source) as $token) {
            if ($token->name === 'T_FOREIGN') {
                $names[] = $token->value;
            }
        }

        self::assertSame(['one', 'two'], $names);
    }
}
