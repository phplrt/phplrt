<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Token;

use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class ChannelTest extends TestCase
{
    private static function createAnnotatedLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('##[^\n]*+', 'T_DOC')->setChannel('documentation');
            $lexer->addPattern('[a-zA-Z_]\w*+', 'T_NAME');
        });
    }

    /**
     * @param iterable<mixed, TokenInterface> $tokens
     * @return array<non-empty-string, ChannelInterface>
     */
    private static function channels(iterable $tokens): array
    {
        $result = [];

        foreach ($tokens as $token) {
            $result[$token->name ?? '#' . $token->id] = $token->channel;
        }

        return $result;
    }

    #[TestDox('A significant token belongs to the default channel')]
    public function testSignificantTokensUseTheDefaultChannel(): void
    {
        $lexer = self::createAnnotatedLexer();
        $source = 'name';

        $channels = self::channels($lexer->lex($source));

        self::assertSame(Channel::Default, $channels['T_NAME']);
    }

    #[TestDox('A hidden token stays in the stream on its own channel')]
    public function testHiddenTokensArePresentInTheStreamOnTheirOwnChannel(): void
    {
        $lexer = self::createAnnotatedLexer();
        $source = 'a b';

        $channels = self::channels($lexer->lex($source));

        self::assertArrayHasKey('T_WHITESPACE', $channels);
        self::assertSame(Channel::Hidden, $channels['T_WHITESPACE']);
    }

    #[TestDox('A user defined channel is exposed by its name and is not a built-in one')]
    public function testUserDefinedChannelIsExposedByItsName(): void
    {
        $lexer = self::createAnnotatedLexer();
        $source = '## note';

        $channels = self::channels($lexer->lex($source));

        self::assertArrayHasKey('T_DOC', $channels);
        self::assertSame('documentation', $channels['T_DOC']->name);
        self::assertNotInstanceOf(Channel::class, $channels['T_DOC']);
    }

    #[TestDox('The same channel name is represented consistently between analyses')]
    public function testTheSameChannelNameIsRepresentedConsistently(): void
    {
        $lexer = self::createAnnotatedLexer();

        $first = self::channels($lexer->lex('## one'));
        $second = self::channels($lexer->lex('## two'));

        self::assertSame($first['T_DOC']->name, $second['T_DOC']->name);
    }

    #[TestDox('An unreadable fragment is reported on the unknown channel')]
    public function testUnrecognizedFragmentIsMarkedAsUnknown(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('\d++', 'T_NUMBER');
        });
        $source = '42 ???';

        $tokens = \iterator_to_array($lexer->lex($source), false);

        $unknown = [];

        foreach ($tokens as $token) {
            if ($token->channel === Channel::Unknown) {
                $unknown[] = $token->value;
            }
        }

        self::assertSame(['???'], $unknown);
        self::assertTokensCoverSource($source, $tokens);
    }

    #[TestDox('The terminal token belongs to the end of input channel')]
    public function testTerminalTokenUsesTheEndOfInputChannel(): void
    {
        $lexer = self::createAnnotatedLexer();
        $source = 'name';

        $tokens = \iterator_to_array($lexer->lex($source), false);

        self::assertSame(Channel::EndOfInput, $tokens[\count($tokens) - 1]->channel);
    }
}
