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
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class GroupCaptureTest extends TestCase
{
    /**
     * Reads the declarations whose parts are captured by the subgroups of a
     * single token definition.
     */
    private static function createLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('%token\h++(?:(\w++):)?(\w++)', 'T_TOKEN');
            $lexer->addPattern('[a-z]++', 'T_NAME');
        });
    }

    /**
     * Returns what the subgroups have captured for every token that has
     * captured something.
     *
     * @return list<list<string>>
     */
    private static function findCaptures(LexerInterface $lexer, string $source): array
    {
        $result = [];

        foreach ($lexer->lex(new Source($source)) as $token) {
            if ($token->captures !== []) {
                $result[] = $token->captures;
            }
        }

        return $result;
    }

    #[TestDox('A token whose definition has captured something carries what its subgroups have captured')]
    public function testCapturedSubgroups(): void
    {
        self::assertSame([['string', 'T_A']], self::findCaptures(self::createLexer(), '%token string:T_A'));
    }

    #[TestDox('A subgroup that has captured nothing is still counted, so a capture keeps its own position')]
    public function testUnreachedSubgroupKeepsThePosition(): void
    {
        self::assertSame([['', 'T_A']], self::findCaptures(self::createLexer(), '%token T_A'));
    }

    #[TestDox('A token definition without subgroups captures nothing')]
    public function testTokenWithoutSubgroups(): void
    {
        self::assertSame([], self::findCaptures(self::createLexer(), 'foo bar'));
    }

    #[TestDox('The captures do not change the fragment the token describes')]
    public function testCapturedTokenDescribesTheWholeFragment(): void
    {
        $lexer = self::createLexer();
        $source = 'foo %token string:T_A';

        $tokens = \iterator_to_array($lexer->lex(new Source($source)), false);
        $declaration = $tokens[2];

        self::assertSame('T_TOKEN', $declaration->name);
        self::assertSame('%token string:T_A', $declaration->value);
        self::assertSame(4, $declaration->offset);
        self::assertSame(\strlen($source) - 4, $declaration->size);
    }

    #[TestDox('The captures do not change the way the source is covered by the stream')]
    public function testSourceIsCoveredByTheStream(): void
    {
        $lexer = self::createLexer();
        $source = '%token string:T_A foo';

        self::assertTokensCoverSource($source, $lexer->lex(new Source($source)));
    }

    #[TestDox('A token entering an embedded lexer keeps what its own subgroups have captured')]
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

        $tokens = \iterator_to_array($lexer->lex(new Source('[note]hello')), false);
        $embedding = $tokens[0];

        self::assertInstanceOf(TokenEmbedding::class, $embedding);
        self::assertSame(['note'], $embedding->captures, 'The subgroups are kept');
        self::assertSame('T_BODY', $embedding[0]->name, 'The embedded tokens are kept as well');
    }

    #[TestDox('A token that is not told how many subgroups it has captures nothing')]
    public function testTokenOutsideOfTheSubgroupTable(): void
    {
        $pattern = '/\G(?|(?:(?:%token\h++(\w++))(*MARK:0))|(?:(?:[a-z]++)(*MARK:1))|(?:(?:\s++)(*MARK:2)))/Ssum';
        $names = [0 => 'T_TOKEN', 1 => 'T_NAME', 2 => 'T_WHITESPACE'];

        $unknown = new Lexer($pattern, names: $names);
        $known = new Lexer($pattern, names: $names, subgroups: [0 => 1]);

        self::assertSame([], self::findCaptures($unknown, '%token T_A foo'));
        self::assertSame([['T_A']], self::findCaptures($known, '%token T_A foo'));
    }
}
