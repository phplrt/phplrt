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
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer-builder')]
final class EmbeddedLexerTest extends TestCase
{
    /**
     * Reads the names written outside of the brackets, while everything
     * between them is read by a lexer of its own.
     */
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

    /**
     * Returns the name and the value of every token the lexer reads.
     *
     * @return list<string>
     */
    private static function tokenize(LexerInterface $lexer, string $source): array
    {
        $result = [];

        foreach ($lexer->lex(new Source($source)) as $token) {
            if ($token->channel === Channel::Default) {
                $result[] = \sprintf('%s(%s)', (string) $token->name, $token->value);
            }
        }

        return $result;
    }

    #[TestDox('A fragment is read by the lexer it has been declared with')]
    public function testLexerGivenAsInstance(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new FragmentLexer());

        $lexer = $builder->build()
            ->toLexer();

        self::assertSame([
            'T_NAME(a)',
            'T_OPEN([)',
            'T_CLOSE(])',
            'T_NAME(b)',
        ], self::tokenize($lexer, 'a [x y] b'));
    }

    #[TestDox('A lexer given as an instance is wrapped into a definition of its own')]
    public function testLexerInstanceIsWrapped(): void
    {
        $builder = self::createBuilder();
        $lexer = new FragmentLexer();

        $definition = $builder->addEmbeddedLexer('fragment', $lexer);

        self::assertInstanceOf(RuntimeEmbeddedLexer::class, $definition);
        self::assertSame($lexer, $definition->lexer);
        self::assertSame(['fragment' => $definition], $builder->lexers);
    }

    #[TestDox('A fragment is read by the lexer the code declared for it produces')]
    public function testLexerGivenAsCode(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new PhpCodeEmbeddedLexer(
            \sprintf('new \\%s()', FragmentLexer::class),
        ));

        $lexer = $builder->build()
            ->toLexer();

        self::assertSame([
            'T_NAME(a)',
            'T_OPEN([)',
            'T_CLOSE(])',
            'T_NAME(b)',
        ], self::tokenize($lexer, 'a [x y] b'));
    }

    #[TestDox('A lexer that cannot be entered is removed')]
    public function testUnreachableLexerRemoval(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new FragmentLexer());
        $builder->addEmbeddedLexer('unused', new FragmentLexer());

        $result = $builder->build();

        self::assertSame(['fragment'], \array_keys($result->lexers));
    }

    #[TestDox('A name is taken by a single lexer, no matter how it has been declared')]
    public function testLexerNameIsUnique(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new FragmentLexer());
        $builder->addLexer('fragment')
            ->addValue(']', 'T_FRAGMENT_CLOSE')
            ->exit();

        self::assertSame(['fragment'], \array_keys($builder->lexers));
        self::assertInstanceOf(LexerBuilder::class, $builder->lexers['fragment']);
    }

    #[TestDox('A token may hand the reading over to a lexer of its own')]
    public function testTransitionToEmbeddedLexer(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new FragmentLexer());

        $result = $builder->build();

        self::assertContains('fragment', $result->transitions);
    }

    #[TestDox('A lexer declared as code that cannot be compiled is reported')]
    public function testMalformedLexerCode(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new PhpCodeEmbeddedLexer('new '));

        $this->expectException(LexerCompilerException::class);
        $this->expectExceptionMessage('The lexer "fragment" cannot be compiled: ');

        $builder->build()
            ->toLexer();
    }

    #[TestDox('A code that produces anything but a lexer is reported')]
    public function testLexerCodeProducingAnythingElse(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedLexer('fragment', new PhpCodeEmbeddedLexer('42'));

        $this->expectException(LexerCompilerException::class);
        $this->expectExceptionMessage(\sprintf(
            'The lexer "fragment" must be an instance of %s, int given',
            LexerInterface::class,
        ));

        $builder->build()
            ->toLexer();
    }
}
