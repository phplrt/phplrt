<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\PhpCodeEmbeddedLexer;
use Phplrt\Lexer\Builder\Definition\Lexer\RuntimeEmbeddedLexer;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Builder\Tests\Stub\FragmentLexer;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer-builder')]
#[Test]
final class EmbeddedLexerTest extends TestCase
{
    private static function createBuilder(): LexerBuilder
    {
        $builder = new LexerBuilder();
        $builder->addPattern('\s++', 'T_WHITESPACE')
            ->hide();
        $builder->addValue('[', 'T_OPEN')
            ->enter('fragment');
        $builder->addValue(']', 'T_CLOSE');
        $builder->addPattern('[a-z]++', 'T_NAME');

        return $builder;
    }

    private static function tokenize(LexerInterface $lexer, string $source): array
    {
        $result = [];

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            if ($token->channel === Channel::Default) {
                $result[] = \sprintf('%s(%s)', (string) $token->name, $token->value);
            }
        }

        return $result;
    }

    public function testLexerGivenAsInstance(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new FragmentLexer());

        $lexer = $builder->build()
            ->toLexer();

        Assert::same(self::tokenize($lexer, 'a [x y] b'), [
            'T_NAME(a)',
            'T_OPEN([)',
            'T_CLOSE(])',
            'T_NAME(b)',
        ]);
    }

    public function testLexerInstanceIsWrapped(): void
    {
        $builder = self::createBuilder();
        $lexer = new FragmentLexer();

        $definition = $builder->addEmbeddedLexer('fragment', $lexer);

        Assert::instanceOf($definition, RuntimeEmbeddedLexer::class);
        Assert::same($definition->lexer, $lexer);
        Assert::same($builder->lexers, ['fragment' => $definition]);
    }

    public function testLexerGivenAsCode(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new PhpCodeEmbeddedLexer(
            \sprintf('new \\%s()', FragmentLexer::class),
        ));

        $lexer = $builder->build()
            ->toLexer();

        Assert::same(self::tokenize($lexer, 'a [x y] b'), [
            'T_NAME(a)',
            'T_OPEN([)',
            'T_CLOSE(])',
            'T_NAME(b)',
        ]);
    }

    public function testUnreachableLexerRemoval(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new FragmentLexer());
        $builder->addEmbeddedLexer('unused', new FragmentLexer());

        $result = $builder->build();

        Assert::same(\array_keys($result->lexers), ['fragment']);
    }

    public function testLexerNameIsUnique(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new FragmentLexer());
        $builder->addLexer('fragment')
            ->addValue(']', 'T_FRAGMENT_CLOSE')
            ->exit();

        Assert::same(\array_keys($builder->lexers), ['fragment']);
        Assert::instanceOf($builder->lexers['fragment'], LexerBuilder::class);
    }

    public function testTransitionToEmbeddedLexer(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new FragmentLexer());

        $result = $builder->build();

        Assert::contains($result->transitions, 'fragment');
    }

    public function testMalformedLexerCode(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new PhpCodeEmbeddedLexer('new '));

        Expect::exception(LexerCompilerException::class)
        ->withMessageContaining('The lexer "fragment" cannot be compiled: ');

        $builder->build()
            ->toLexer();
    }

    public function testLexerCodeProducingAnythingElse(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new PhpCodeEmbeddedLexer('42'));

        Expect::exception(LexerCompilerException::class)
        ->withMessage(\sprintf(
            'The lexer "fragment" must be an instance of %s, int given',
            LexerInterface::class,
        ));

        $builder->build()
            ->toLexer();
    }
}
