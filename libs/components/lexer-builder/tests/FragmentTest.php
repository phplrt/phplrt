<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer-compiler')]
final class FragmentTest extends TestCase
{
    #[TestDox('A piece of an expression is written into the expression referring to it')]
    public function testFragmentIsWrittenIntoExpression(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $token = $lexer->addPattern('(?&DIGIT)++', 'T_NUMBER');

        $lexer->build();

        self::assertSame('(?:[0-9])++', $token->regex);
    }

    #[TestDox('A piece written of another piece is written of what that one stands for')]
    public function testFragmentIsWrittenOfAnotherFragment(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $lexer->addFragment('EXP', '[eE][+-]?(?&DIGIT)++');
        $token = $lexer->addPattern('(?&DIGIT)++(?&EXP)?', 'T_NUMBER');

        $lexer->build();

        self::assertSame('(?:[0-9])++(?:[eE][+-]?(?:[0-9])++)?', $token->regex);
    }

    #[TestDox('A piece is written into the expression whatever the order they are declared in')]
    public function testFragmentIsDeclaredAfterUse(): void
    {
        $lexer = new LexerBuilder();
        $token = $lexer->addPattern('(?&DIGIT)++', 'T_NUMBER');
        $lexer->addFragment('DIGIT', '[0-9]');

        $lexer->build();

        self::assertSame('(?:[0-9])++', $token->regex);
    }

    #[TestDox('A piece is written into the expressions of every state')]
    public function testFragmentReachesEveryState(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('WORD', '[a-z]++');
        $lexer->addPattern('"', 'T_QUOTE_OPEN')
            ->enter('string');

        $embedded = $lexer->addLexer('string');
        $token = $embedded->addPattern('(?&WORD)', 'T_TEXT');

        $lexer->build();

        self::assertSame('(?:[a-z]++)', $token->regex);
    }

    #[TestDox('A state knows the pieces it declares on its own')]
    public function testStateDeclaresFragmentOfItsOwn(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('WORD', '[a-z]++');
        $lexer->addPattern('"', 'T_QUOTE_OPEN')
            ->enter('string');

        $embedded = $lexer->addLexer('string');
        $embedded->addFragment('UPPER', '[A-Z]++');
        $token = $embedded->addPattern('(?&WORD)|(?&UPPER)', 'T_TEXT');

        $lexer->build();

        self::assertSame('(?:[a-z]++)|(?:[A-Z]++)', $token->regex);
    }

    #[TestDox('A token recognizing the value it is written of is left alone')]
    public function testValueTokenIsNotRewritten(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $token = $lexer->addValue('(?&DIGIT)', 'T_TEXT');

        $lexer->build();

        self::assertSame('(?&DIGIT)', $token->value);
    }

    #[TestDox('A reference spelled after a backslash is read as the characters it is written of')]
    public function testEscapedReferenceIsNotRewritten(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $token = $lexer->addPattern('\\(?&DIGIT\\)', 'T_TEXT');

        $lexer->build();

        self::assertSame('\\(?&DIGIT\\)', $token->regex);
    }

    #[TestDox('An expression calling a subpattern of its own is left alone')]
    public function testSubpatternCallIsNotRewritten(): void
    {
        $lexer = new LexerBuilder();
        $token = $lexer->addPattern('\((?<in>[^()]|(?&in))*+\)', 'T_NESTED');

        $lexer->build();

        self::assertSame('\((?<in>[^()]|(?&in))*+\)', $token->regex);
    }

    #[TestDox('A piece that has not been declared is reported')]
    public function testUnknownFragmentIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains(
            'refers to the "DIGIT" fragment, which has not been declared',
        );

        $lexer = new LexerBuilder();
        $lexer->addPattern('(?&DIGIT)++', 'T_NUMBER');

        $lexer->build();
    }

    #[TestDox('A piece written of itself is reported')]
    public function testRecursiveFragmentIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains('(A) fragment is written of itself');

        $lexer = new LexerBuilder();
        $lexer->addFragment('A', '[a-z](?&A)?');
        $lexer->addPattern('(?&A)', 'T_TEXT');

        $lexer->build();
    }

    #[TestDox('A piece written of a piece written of it is reported')]
    public function testIndirectlyRecursiveFragmentIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains('fragment is written of itself');

        $lexer = new LexerBuilder();
        $lexer->addFragment('A', '(?&B)');
        $lexer->addFragment('B', '(?&A)');
        $lexer->addPattern('(?&A)', 'T_TEXT');

        $lexer->build();
    }

    #[TestDox('A piece is removed by the name it has been added under')]
    public function testFragmentIsRemoved(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $lexer->removeFragment('DIGIT');

        self::assertSame([], $lexer->fragments);
    }

    #[TestDox('The lexer reads what the expressions the pieces are written into recognize')]
    public function testLexerReadsWhatFragmentsDescribe(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $lexer->addFragment('EXP', '[eE][+-]?(?&DIGIT)++');
        $lexer->addPattern('(?&DIGIT)++(\.(?&DIGIT)++)?(?&EXP)?', 'T_NUMBER');
        $lexer->addPattern('\s++', 'T_WHITESPACE')
            ->hide();

        $result = $lexer->build()
            ->toLexer();

        $tokens = [];

        foreach ($result->lex(StringSource::createFromString('1 12.5 3e10')) as $token) {
            $tokens[] = $token->value;
        }

        self::assertSame(['1', '12.5', '3e10', ''], $tokens);
    }

    #[TestDox('The expression a token is recognized by is rewritten by a pass')]
    public function testRegexIsWritable(): void
    {
        $definition = new RegexTokenDefinition('[0-9]', 'T_NUMBER');

        self::assertSame('[a-z]', $definition->setRegex('[a-z]')->regex);
    }
}
