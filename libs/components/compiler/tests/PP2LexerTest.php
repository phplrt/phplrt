<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\TokenEmbedding;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP2LexerTest extends TestCase
{
    private const string GRAMMAR = __DIR__ . '/../resources/pp2.pp3';

    private static ?LexerInterface $lexer = null;

    private static function tokenize(string $source): array
    {
        $lexer = self::$lexer ??= (new Compiler())
            ->load(FileSource::createFromPathname(self::GRAMMAR))
            ->build()
            ->lexer
            ->toLexer();

        $result = [];

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            if ($token->channel === Channel::Default) {
                $result[] = $token;
            }
        }

        return $result;
    }

    private static function describeTokens(string $source): array
    {
        $result = [];

        foreach (self::tokenize($source) as $token) {
            $result[] = \sprintf('%s(%s)', (string) $token->name, $token->value);
        }

        return $result;
    }

    private static function describeCaptures(string $source): array
    {
        $result = [];

        foreach (self::tokenize($source) as $token) {
            \assert($token instanceof Token);

            if ($token->captures !== []) {
                $result[] = $token->captures;
            }
        }

        return $result;
    }

    public function testTokenDeclarationValues(): void
    {
        $source = '%token string:T_CHAR [^"]++ -> default';

        Assert::same(self::describeTokens($source), [\sprintf('T_TOKEN(%s)', $source)]);
        Assert::same(self::describeCaptures($source), [['string', 'T_CHAR', '[^"]++', 'default']]);
    }

    public function testUnwrittenValueKeepsThePosition(): void
    {
        Assert::same(self::describeCaptures('%token T_A a'), [['', 'T_A', 'a', '']]);
    }

    public function testPatternSpelledLikeComment(): void
    {
        Assert::same(self::describeCaptures('%skip T_COMMENT //[^\n]*'), [['', 'T_COMMENT', '//[^\n]*', '']]);
    }

    public function testDeclarationEnd(): void
    {
        Assert::same(self::describeTokens("%token T_A a // and a comment\nA : <T_A>"), [
            'T_TOKEN(%token T_A a)',
            'T_NAME(A)',
            'T_EQ(:)',
            'T_ANGLE_OPEN(<)',
            'T_NAME(T_A)',
            'T_ANGLE_CLOSE(>)',
        ]);
    }

    public function testColons(): void
    {
        Assert::same(self::describeTokens('A ::= ::T_A::'), [
            'T_NAME(A)',
            'T_EQ(::=)',
            'T_DOUBLE_COLON(::)',
            'T_NAME(T_A)',
            'T_DOUBLE_COLON(::)',
        ]);
    }

    public function testPhpCodeBoundaries(): void
    {
        $source = <<<'PP2'
            A -> { return ['}', "{"]; /* } */ } : <T_A>
            PP2;

        $tokens = self::tokenize($source);
        $php = $tokens[1];

        Assert::instanceOf($php, TokenEmbedding::class);
        Assert::same($php->name, 'T_PHP');
        Assert::same($php->value, '-> ');
        Assert::same(\substr(
            $source,
            $php->children[0]->offset,
            $php->offset + $php->size - $php->children[0]->offset,
        ), '{ return [\'}\', "{"]; /* } */ }');

        Assert::same($tokens[2]->name, 'T_EQ');
    }

    public function testArrowIsNotAPhpCodeBlock(): void
    {
        Assert::same(self::describeTokens('A -> \App\Node'), [
            'T_NAME(A)',
            'T_ARROW(->)',
            'T_NAME(\App\Node)',
        ]);
    }
}
