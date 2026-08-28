<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\TokenEmbedding;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer')]
#[Test]
final class GroupCaptureTest extends TestCase
{
    private static function createLexer(iterable $skip = Lexer::DEFAULT_SKIP_CHANNELS): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('%token\h++(?:(\w++):)?(\w++)', 'T_TOKEN');
            $lexer->addPattern('[a-z]++', 'T_NAME');
        }, $skip);
    }

    private static function findCaptures(LexerInterface $lexer, string $source): array
    {
        $result = [];

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            if ($token->captures !== []) {
                $result[] = $token->captures;
            }
        }

        return $result;
    }

    public function testCapturedSubgroups(): void
    {
        Assert::same(self::findCaptures(self::createLexer(), '%token string:T_A'), [['string', 'T_A']]);
    }

    public function testUnreachedSubgroupKeepsThePosition(): void
    {
        Assert::same(self::findCaptures(self::createLexer(), '%token T_A'), [['', 'T_A']]);
    }

    public function testTokenWithoutSubgroups(): void
    {
        Assert::same(self::findCaptures(self::createLexer(), 'foo bar'), []);
    }

    public function testCapturedTokenDescribesTheWholeFragment(): void
    {
        $lexer = self::createLexer(skip: []);
        $source = 'foo %token string:T_A';

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString($source)), false);
        $declaration = $tokens[2];

        Assert::same($declaration->name, 'T_TOKEN');
        Assert::same($declaration->value, '%token string:T_A');
        Assert::same($declaration->offset, 4);
        Assert::same($declaration->size, \strlen($source) - 4);
    }

    public function testSourceIsCoveredByTheStream(): void
    {
        $lexer = self::createLexer(skip: []);
        $source = '%token string:T_A foo';

        self::assertTokensCoverSource($source, $lexer->lex(StringSource::createFromString($source)));
    }

    public function testCapturesSurviveTheEmbedding(): void
    {
        $lexer = self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\[(\w++)\]', 'T_OPEN')->enter('body');
            $lexer->addEmbeddedLexer('body', new class implements LexerInterface {
                public function lex(ReadableInterface $source, int $offset = 0): iterable
                {
                    $content = $source->content;

                    return [
                        new Token(100, 'T_BODY', Channel::Default, \substr($content, $offset), $offset),
                        new EndOfInputToken(\strlen($content)),
                    ];
                }
            });
        });

        $tokens = \iterator_to_array($lexer->lex(StringSource::createFromString('[note]hello')), false);
        $embedding = $tokens[0];

        Assert::instanceOf($embedding, TokenEmbedding::class);
        Assert::same($embedding->captures, ['note'], 'The subgroups are kept');
        Assert::same($embedding[0]->name, 'T_BODY', 'The embedded tokens are kept as well');
    }

    public function testTokenOutsideOfTheSubgroupTable(): void
    {
        $pattern = '/\G(?|(?:(?:%token\h++(\w++))(*MARK:0))|(?:(?:[a-z]++)(*MARK:1))|(?:(?:\s++)(*MARK:2)))/Ssum';
        $names = [0 => 'T_TOKEN', 1 => 'T_NAME', 2 => 'T_WHITESPACE'];

        $unknown = new Lexer($pattern, names: $names);
        $known = new Lexer($pattern, names: $names, subgroups: [0 => 1]);

        Assert::same(self::findCaptures($unknown, '%token T_A foo'), []);
        Assert::same(self::findCaptures($known, '%token T_A foo'), [['T_A']]);
    }
}
