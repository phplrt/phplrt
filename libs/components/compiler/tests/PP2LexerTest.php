<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\TokenEmbedding;
use Phplrt\Source\File;
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/compiler')]
final class PP2LexerTest extends TestCase
{
    /**
     * The grammar describing the format, which is where the lexer of it comes
     * from.
     *
     * @var non-empty-string
     */
    private const string GRAMMAR = __DIR__ . '/../resources/pp2.pp3';

    /**
     * Compiling the grammar takes long enough to be done once for the whole
     * test case.
     */
    private static ?LexerInterface $lexer = null;

    /**
     * @return list<TokenInterface>
     */
    private static function tokenize(string $source): array
    {
        $lexer = self::$lexer ??= new Compiler()
            ->load(new File(self::GRAMMAR))
            ->build()
            ->lexer
            ->toLexer();

        $result = [];

        foreach ($lexer->lex(new Source($source)) as $token) {
            if ($token->channel === Channel::Default) {
                $result[] = $token;
            }
        }

        return $result;
    }

    /**
     * Returns the name and the value of every token reaching the parser.
     *
     * @return list<string>
     */
    private static function describeTokens(string $source): array
    {
        $result = [];

        foreach (self::tokenize($source) as $token) {
            $result[] = \sprintf('%s(%s)', (string) $token->name, $token->value);
        }

        return $result;
    }

    /**
     * Returns what the subgroups of every token have captured.
     *
     * @return list<list<string>>
     */
    private static function describeCaptures(string $source): array
    {
        $result = [];

        foreach (self::tokenize($source) as $token) {
            if ($token->captures !== []) {
                $result[] = $token->captures;
            }
        }

        return $result;
    }

    #[TestDox('The values of a token declaration are captured by the subgroups of a single token')]
    public function testTokenDeclarationValues(): void
    {
        $source = '%token string:T_CHAR [^"]++ -> default';

        self::assertSame([\sprintf('T_TOKEN(%s)', $source)], self::describeTokens($source));
        self::assertSame([['string', 'T_CHAR', '[^"]++', 'default']], self::describeCaptures($source));
    }

    #[TestDox('A value that is not written keeps the position of its own subgroup')]
    public function testUnwrittenValueKeepsThePosition(): void
    {
        self::assertSame([['', 'T_A', 'a', '']], self::describeCaptures('%token T_A a'));
    }

    #[TestDox('A pattern of a declaration is read whole even when it is spelled like a comment')]
    public function testPatternSpelledLikeComment(): void
    {
        self::assertSame([['', 'T_COMMENT', '//[^\n]*', '']], self::describeCaptures('%skip T_COMMENT //[^\n]*'));
    }

    #[TestDox('A declaration ends where its own values end')]
    public function testDeclarationEnd(): void
    {
        self::assertSame([
            'T_TOKEN(%token T_A a)',
            'T_NAME(A)',
            'T_EQ(:)',
            'T_ANGLE_OPEN(<)',
            'T_NAME(T_A)',
            'T_ANGLE_CLOSE(>)',
        ], self::describeTokens("%token T_A a // and a comment\nA : <T_A>"));
    }

    #[TestDox('The colons of a skipped token reference are told from the "::=" separator')]
    public function testColons(): void
    {
        self::assertSame([
            'T_NAME(A)',
            'T_EQ(::=)',
            'T_DOUBLE_COLON(::)',
            'T_NAME(T_A)',
            'T_DOUBLE_COLON(::)',
        ], self::describeTokens('A ::= ::T_A::'));
    }

    #[TestDox('A reducer written as code is read up to the brace closing it')]
    public function testPhpCodeBoundaries(): void
    {
        $source = <<<'PP2'
            A -> { return ['}', "{"]; /* } */ } : <T_A>
            PP2;

        $tokens = self::tokenize($source);
        $php = $tokens[1];

        self::assertInstanceOf(TokenEmbedding::class, $php);
        self::assertSame('T_PHP', $php->name);
        self::assertSame('-> ', $php->value);
        self::assertSame('{ return [\'}\', "{"]; /* } */ }', \substr(
            $source,
            $php->children[0]->offset,
            $php->offset + $php->size - $php->children[0]->offset,
        ));

        self::assertSame('T_EQ', $tokens[2]->name);
    }

    #[TestDox('A reducer written as a class name is told from a reducer written as code')]
    public function testArrowIsNotAPhpCodeBlock(): void
    {
        self::assertSame([
            'T_NAME(A)',
            'T_ARROW(->)',
            'T_NAME(\App\Node)',
        ], self::describeTokens('A -> \App\Node'));
    }
}
