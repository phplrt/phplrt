<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer-compiler')]
#[Test]
final class FragmentTest extends TestCase
{
    public function testFragmentIsWrittenIntoExpression(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $token = $lexer->addPattern('(?&DIGIT)++', 'T_NUMBER');

        $lexer->build();

        Assert::same($token->regex, '(?:[0-9])++');
    }

    public function testFragmentIsWrittenOfAnotherFragment(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $lexer->addFragment('EXP', '[eE][+-]?(?&DIGIT)++');
        $token = $lexer->addPattern('(?&DIGIT)++(?&EXP)?', 'T_NUMBER');

        $lexer->build();

        Assert::same($token->regex, '(?:[0-9])++(?:[eE][+-]?(?:[0-9])++)?');
    }

    public function testFragmentIsDeclaredAfterUse(): void
    {
        $lexer = new LexerBuilder();
        $token = $lexer->addPattern('(?&DIGIT)++', 'T_NUMBER');
        $lexer->addFragment('DIGIT', '[0-9]');

        $lexer->build();

        Assert::same($token->regex, '(?:[0-9])++');
    }

    public function testFragmentReachesEveryState(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('WORD', '[a-z]++');
        $lexer->addPattern('"', 'T_QUOTE_OPEN')
            ->enter('string');

        $embedded = $lexer->addLexer('string');
        $token = $embedded->addPattern('(?&WORD)', 'T_TEXT');

        $lexer->build();

        Assert::same($token->regex, '(?:[a-z]++)');
    }

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

        Assert::same($token->regex, '(?:[a-z]++)|(?:[A-Z]++)');
    }

    public function testValueTokenIsNotRewritten(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $token = $lexer->addValue('(?&DIGIT)', 'T_TEXT');

        $lexer->build();

        Assert::same($token->value, '(?&DIGIT)');
    }

    public function testEscapedReferenceIsNotRewritten(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $token = $lexer->addPattern('\\(?&DIGIT\\)', 'T_TEXT');

        $lexer->build();

        Assert::same($token->regex, '\\(?&DIGIT\\)');
    }

    public function testSubpatternCallIsNotRewritten(): void
    {
        $lexer = new LexerBuilder();
        $token = $lexer->addPattern('\((?<in>[^()]|(?&in))*+\)', 'T_NESTED');

        $lexer->build();

        Assert::same($token->regex, '\((?<in>[^()]|(?&in))*+\)');
    }

    public function testUnknownFragmentIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining(
            'refers to the "DIGIT" fragment, which has not been declared',
        );

        $lexer = new LexerBuilder();
        $lexer->addPattern('(?&DIGIT)++', 'T_NUMBER');

        $lexer->build();
    }

    public function testRecursiveFragmentIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('(A) fragment is written of itself');

        $lexer = new LexerBuilder();
        $lexer->addFragment('A', '[a-z](?&A)?');
        $lexer->addPattern('(?&A)', 'T_TEXT');

        $lexer->build();
    }

    public function testIndirectlyRecursiveFragmentIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('fragment is written of itself');

        $lexer = new LexerBuilder();
        $lexer->addFragment('A', '(?&B)');
        $lexer->addFragment('B', '(?&A)');
        $lexer->addPattern('(?&A)', 'T_TEXT');

        $lexer->build();
    }

    public function testFragmentIsRemoved(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addFragment('DIGIT', '[0-9]');
        $lexer->removeFragment('DIGIT');

        Assert::same($lexer->fragments, []);
    }

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

        Assert::same($tokens, ['1', '12.5', '3e10', '']);
    }

    public function testRegexIsWritable(): void
    {
        $definition = new RegexTokenDefinition('[0-9]', 'T_NUMBER');

        Assert::same($definition->setRegex('[a-z]')->regex, '[a-z]');
    }
}
