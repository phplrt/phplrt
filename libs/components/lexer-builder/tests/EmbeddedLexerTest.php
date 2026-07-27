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

        foreach ($lexer->lex($source) as $token) {
            if ($token->channel === Channel::Default) {
                $result[] = \sprintf('%s(%s)', (string) $token->name, $token->value);
            }
        }

        return $result;
    }

    #[TestDox('A state is read by the lexer it has been declared with')]
    public function testStateReadByLexerInstance(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedState('fragment', new FragmentLexer());

        $lexer = $builder->build()
            ->toLexer();

        self::assertSame([
            'T_NAME(a)',
            'T_OPEN([)',
            'T_FRAGMENT(x y)',
            'T_CLOSE(])',
            'T_NAME(b)',
        ], self::tokenize($lexer, 'a [x y] b'));
    }

    #[TestDox('A lexer given as an instance is wrapped into a definition of its own')]
    public function testLexerInstanceIsWrapped(): void
    {
        $builder = self::createBuilder();
        $lexer = new FragmentLexer();

        $definition = $builder->addEmbeddedState('fragment', $lexer);

        self::assertInstanceOf(RuntimeEmbeddedLexer::class, $definition);
        self::assertSame($lexer, $definition->lexer);
        self::assertSame(['fragment' => $definition], $builder->embeddedStates);
    }

    #[TestDox('A state is read by the lexer the code declared for it produces')]
    public function testStateReadByLexerCode(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedState('fragment', new PhpCodeEmbeddedLexer(
            \sprintf('new \\%s()', FragmentLexer::class),
        ));

        $lexer = $builder->build()
            ->toLexer();

        self::assertSame([
            'T_NAME(a)',
            'T_OPEN([)',
            'T_FRAGMENT(x y)',
            'T_CLOSE(])',
            'T_NAME(b)',
        ], self::tokenize($lexer, 'a [x y] b'));
    }

    #[TestDox('A state that cannot be entered is removed along with the lexer reading it')]
    public function testUnreachableStateRemoval(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedState('fragment', new FragmentLexer());
        $builder->addEmbeddedState('unused', new FragmentLexer());

        $result = $builder->build();

        self::assertSame(['fragment'], \array_keys($result->embeddedStates));
    }

    #[TestDox('A state name is taken by a single state, whatever it is read by')]
    public function testStateNameIsUnique(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedState('fragment', new FragmentLexer());
        $builder->addState('fragment')
            ->addValue(']', 'T_FRAGMENT_CLOSE')
            ->exit();

        self::assertSame([], $builder->embeddedStates);
        self::assertSame(['fragment'], \array_keys($builder->states));
    }

    #[TestDox('A transition to a state read by a lexer of its own is allowed')]
    public function testTransitionToEmbeddedState(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedState('fragment', new FragmentLexer());

        $result = $builder->build();

        self::assertContains('fragment', $result->transitions);
    }

    #[TestDox('A lexer declared as code that cannot be compiled is reported')]
    public function testMalformedLexerCode(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedState('fragment', new PhpCodeEmbeddedLexer('new '));

        $this->expectException(LexerCompilerException::class);
        $this->expectExceptionMessage('The lexer of the state "fragment" cannot be compiled: ');

        $builder->build()
            ->toLexer();
    }

    #[TestDox('A code that produces anything but a lexer is reported')]
    public function testLexerCodeProducingAnythingElse(): void
    {
        $builder = self::createBuilder();
        $builder->addEmbeddedState('fragment', new PhpCodeEmbeddedLexer('42'));

        $this->expectException(LexerCompilerException::class);
        $this->expectExceptionMessage(\sprintf(
            'The lexer of the state "fragment" must be an instance of %s, int given',
            LexerInterface::class,
        ));

        $builder->build()
            ->toLexer();
    }
}
