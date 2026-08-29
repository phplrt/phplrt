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
final class ChannelTest extends TestCase
{
    private static function createAnnotatedLexer(iterable $skip = Lexer::DEFAULT_SKIP_CHANNELS): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('##[^\n]*+', 'T_DOC')->setChannel('documentation');
            $lexer->addPattern('[a-zA-Z_]\w*+', 'T_NAME');
        }, $skip);
    }

    private static function channels(iterable $tokens): array
    {
        $result = [];

        foreach ($tokens as $token) {
            $result[$token->name ?? '#' . $token->id] = $token->channel;
        }

        return $result;
    }

    public function testSignificantTokensUseTheDefaultChannel(): void
    {
        $lexer = self::createAnnotatedLexer();
        $source = 'name';

        $channels = self::channels($lexer->lex(StringSource::createFromString($source)));

        Assert::same($channels['T_NAME'], Channel::Default);
    }

    public function testHiddenTokensAreLeftOutOfTheStream(): void
    {
        $lexer = self::createAnnotatedLexer();
        $source = 'a b';

        $channels = self::channels($lexer->lex(StringSource::createFromString($source)));

        Assert::array($channels)->doesNotHaveKeys('T_WHITESPACE');
    }

    public function testHiddenTokensArePresentInTheStreamOnTheirOwnChannel(): void
    {
        $lexer = self::createAnnotatedLexer(skip: []);
        $source = 'a b';

        $channels = self::channels($lexer->lex(StringSource::createFromString($source)));

        Assert::array($channels)->hasKeys('T_WHITESPACE');
        Assert::same($channels['T_WHITESPACE'], Channel::Hidden);
    }

    public function testUserDefinedChannelIsExposedByItsName(): void
    {
        $lexer = self::createAnnotatedLexer();
        $source = '## note';

        $channels = self::channels($lexer->lex(StringSource::createFromString($source)));

        Assert::array($channels)->hasKeys('T_DOC');
        Assert::same($channels['T_DOC']->name, 'documentation');
        Assert::false($channels['T_DOC'] instanceof Channel);
    }

    public function testTheSameChannelNameIsRepresentedConsistently(): void
    {
        $lexer = self::createAnnotatedLexer();

        $first = self::channels($lexer->lex(StringSource::createFromString('## one')));
        $second = self::channels($lexer->lex(StringSource::createFromString('## two')));

        Assert::same($second['T_DOC']->name, $first['T_DOC']->name);
    }

    public function testUnrecognizedFragmentIsMarkedAsUnknown(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('\d++', 'T_NUMBER');
        }, skip: []);
        $source = '42 ???';

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString($source)), false);

        $unknown = [];

        foreach ($tokens as $token) {
            if ($token->channel === Channel::Unknown) {
                $unknown[] = $token->value;
            }
        }

        Assert::same($unknown, ['???']);
        self::assertTokensCoverSource($source, $tokens);
    }

    public function testTerminalTokenUsesTheEndOfInputChannel(): void
    {
        $lexer = self::createAnnotatedLexer();
        $source = 'name';

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString($source)), false);

        Assert::same($tokens[\count($tokens) - 1]->channel, Channel::EndOfInput);
    }
}
