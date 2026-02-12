<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer\Tests;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
class CompatibilityTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testLexerCompatibility(): void
    {
        new class () implements LexerInterface {
            public function lex(mixed $source): iterable {}
        };
    }

    #[DoesNotPerformAssertions]
    public function testTokenCompatibility(): void
    {
        new class () implements TokenInterface {
            public string|int $name;
            public ?ChannelInterface $channel;
            public int $offset;
            public string $value;
            public int $bytes;

            public function __toString(): string {}
        };
    }

    #[DoesNotPerformAssertions]
    public function testLexerExceptionCompatibility(): void
    {
        new class extends \Exception implements LexerExceptionInterface {};
    }

    #[DoesNotPerformAssertions]
    public function testLexerRuntimeExceptionCompatibility(): void
    {
        new class extends \Exception implements RuntimeExceptionInterface
        {
            public ReadableInterface $source;
            public TokenInterface $token;
        };
    }
}
