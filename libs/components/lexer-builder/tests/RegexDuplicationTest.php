<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer-compiler')]
final class RegexDuplicationTest extends TestCase
{
    #[TestDox('Two expressions written the same way are reported')]
    public function testDuplicateRegexIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains(
            'The /\d++/ (T_SECOND) token definition recognizes the same fragment '
            . 'as the /\d++/ (T_FIRST) one declared before it',
        );

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++', 'T_FIRST');
        $lexer->addPattern('\d++', 'T_SECOND');

        $lexer->build();
    }

    #[TestDox('Two values written the same way are reported')]
    public function testDuplicateValueIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains('recognizes the same fragment');

        $lexer = new LexerBuilder();
        $lexer->addValue('::', 'T_FIRST');
        $lexer->addValue('::', 'T_SECOND');

        $lexer->build();
    }

    #[TestDox('An expression recognizing what a value does is reported')]
    public function testValueRepeatedByRegexIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains('recognizes the same fragment');

        $lexer = new LexerBuilder();
        $lexer->addPattern('::', 'T_FIRST');
        $lexer->addValue('::', 'T_SECOND');

        $lexer->build();
    }

    #[TestDox('An expression escaping what stands for itself recognizes what a value does')]
    public function testEscapedValueRepeatedByRegexIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains('recognizes the same fragment');

        $lexer = new LexerBuilder();
        $lexer->addPattern('\.', 'T_FIRST');
        $lexer->addValue('.', 'T_SECOND');

        $lexer->build();
    }

    #[TestDox('An expression meaning more than itself recognizes another fragment than the value spelled the same way')]
    public function testRegexIsNotAValueSpelledTheSameWay(): void
    {
        $lexer = new LexerBuilder();
        $first = $lexer->addPattern('\d+', 'T_FIRST');
        $second = $lexer->addValue('\d+', 'T_SECOND');

        $lexer->build();

        self::assertSame('\d+', $first->regex);
        self::assertSame('\d+', $second->value);
    }

    #[TestDox('A fragment of any kind recognizes another one than the value of that sign')]
    public function testAnyCharacterIsNotADot(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('.', 'T_FIRST');
        $lexer->addValue('.', 'T_SECOND');

        self::assertCount(3, $lexer->build()->tokens);
    }

    #[TestDox('A class escaping a dash recognizes another fragment than the one spelling a range')]
    public function testEscapedDashInsideClassIsNotARange(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('[a\-z]', 'T_FIRST');
        $lexer->addPattern('[a-z]', 'T_SECOND');

        self::assertCount(3, $lexer->build()->tokens);
    }

    #[TestDox('Expressions recognizing different fragments are left alone')]
    public function testDifferentExpressionsAreAllowed(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++', 'T_NUMBER');
        $lexer->addPattern('[a-z]++', 'T_NAME');
        $lexer->addValue('+', 'T_PLUS');

        self::assertCount(4, $lexer->build()->tokens);
    }
}
