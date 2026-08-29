<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer-compiler')]
#[Test]
final class RegexDuplicationTest extends TestCase
{
    public function testDuplicateRegexIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining(
            'The /\d++/ (T_SECOND) token definition recognizes the same fragment '
            . 'as the /\d++/ (T_FIRST) one declared before it',
        );

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++', 'T_FIRST');
        $lexer->addPattern('\d++', 'T_SECOND');

        $lexer->build();
    }

    public function testDuplicateValueIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('recognizes the same fragment');

        $lexer = new LexerBuilder();
        $lexer->addValue('::', 'T_FIRST');
        $lexer->addValue('::', 'T_SECOND');

        $lexer->build();
    }

    public function testValueRepeatedByRegexIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('recognizes the same fragment');

        $lexer = new LexerBuilder();
        $lexer->addPattern('::', 'T_FIRST');
        $lexer->addValue('::', 'T_SECOND');

        $lexer->build();
    }

    public function testEscapedValueRepeatedByRegexIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('recognizes the same fragment');

        $lexer = new LexerBuilder();
        $lexer->addPattern('\.', 'T_FIRST');
        $lexer->addValue('.', 'T_SECOND');

        $lexer->build();
    }

    public function testRegexIsNotAValueSpelledTheSameWay(): void
    {
        $lexer = new LexerBuilder();
        $first = $lexer->addPattern('\d+', 'T_FIRST');
        $second = $lexer->addValue('\d+', 'T_SECOND');

        $lexer->build();

        Assert::same($first->regex, '\d+');
        Assert::same($second->value, '\d+');
    }

    public function testAnyCharacterIsNotADot(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('.', 'T_FIRST');
        $lexer->addValue('.', 'T_SECOND');

        Assert::count($lexer->build()->tokens, 3);
    }

    public function testEscapedDashInsideClassIsNotARange(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('[a\-z]', 'T_FIRST');
        $lexer->addPattern('[a-z]', 'T_SECOND');

        Assert::count($lexer->build()->tokens, 3);
    }

    public function testDifferentExpressionsAreAllowed(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++', 'T_NUMBER');
        $lexer->addPattern('[a-z]++', 'T_NAME');
        $lexer->addValue('+', 'T_PLUS');

        Assert::count($lexer->build()->tokens, 4);
    }
}
