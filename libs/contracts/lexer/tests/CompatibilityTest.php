<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer\Tests;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    public function testLexerCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements LexerInterface {
            public function lex($source): iterable {}
        };
    }

    /**
     * @requires PHP 8.0
     */
    public function testLexerWithMixedCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements LexerInterface {
            public function lex(mixed $source): iterable {}
        };
    }

    public function testTokenCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements TokenInterface {
            public function getName(): string {}
            public function getOffset(): int {}
            public function getValue(): string {}
            public function getBytes(): int {}
        };
    }
}
