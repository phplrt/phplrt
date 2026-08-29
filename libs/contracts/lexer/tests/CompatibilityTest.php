<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer\Tests;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Testo\Assert\ExpectNoAssertions;
use Testo\Test;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
#[Test]
class CompatibilityTest extends TestCase
{
    #[ExpectNoAssertions]
    public function testLexerCompatibility(): void
    {
        new class implements LexerInterface {
            public function lex(ReadableInterface $source, int $offset = 0): iterable
            {
                return [];
            }
        };
    }

    #[ExpectNoAssertions]
    public function testTokenCompatibility(): void
    {
        new class implements TokenInterface {
            public int $id;
            public ?string $name;
            public ChannelInterface $channel;
            public ReadableInterface $source;
            public int $offset;
            public int $size;
            public string $value;
            public int $bytes;

            public function __toString(): string
            {
                return '';
            }
        };
    }

    #[ExpectNoAssertions]
    public function testLexerExceptionCompatibility(): void
    {
        new class extends \Exception implements LexerExceptionInterface {};
    }

    #[ExpectNoAssertions]
    public function testLexerRuntimeExceptionCompatibility(): void
    {
        new class extends \Exception implements RuntimeExceptionInterface {
            public ReadableInterface $source;
            public TokenInterface $token;
        };
    }
}
