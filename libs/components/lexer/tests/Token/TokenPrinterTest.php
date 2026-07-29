<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Token;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Lexer\Tests\TestCase;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Printer\PrettyTokenPrinter;
use Phplrt\Lexer\Token\Printer\TokenPrinterInterface;
use Phplrt\Lexer\Token\Token;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class TokenPrinterTest extends TestCase
{
    private static function createPrinter(): TokenPrinterInterface
    {
        return new PrettyTokenPrinter();
    }

    private static function createToken(string $value, ?string $name = null, ?Channel $channel = null): Token
    {
        return new Token(
            id: 1,
            name: $name,
            channel: $channel ?? Channel::Default,
            value: $value,
            offset: 0,
        );
    }

    #[TestDox('The terminal token is printed as "end of input"')]
    public function testTerminalTokenIsPrintedAsEndOfInput(): void
    {
        $printer = self::createPrinter();
        $token = new EndOfInputToken(0);

        self::assertSame('end of input', $printer->print($token));
    }

    #[TestDox('A printed token mentions its value')]
    public function testPrintedTokenMentionsItsValue(): void
    {
        $printer = self::createPrinter();
        $token = self::createToken('example');

        self::assertStringContainsString('example', $printer->print($token));
    }

    #[TestDox('A printed token mentions its name')]
    public function testPrintedTokenMentionsItsName(): void
    {
        $printer = self::createPrinter();
        $token = self::createToken('example', 'T_NAME');

        self::assertStringContainsString('T_NAME', $printer->print($token));
    }

    #[TestDox('A printed token stays readable on a single line')]
    public function testPrintedTokenStaysOnASingleLine(): void
    {
        $printer = self::createPrinter();
        $token = self::createToken("first\nsecond");

        self::assertStringNotContainsString("\n", $printer->print($token));
    }

    #[TestDox('A long lexeme is shortened when printed')]
    public function testLongValueIsShortened(): void
    {
        $printer = self::createPrinter();
        $value = \str_repeat('a', 1000);
        $token = self::createToken($value);

        self::assertLessThan(\strlen($value), \strlen($printer->print($token)));
    }

    #[TestDox('A token is convertible to a string')]
    public function testTokenIsConvertibleToString(): void
    {
        $token = self::createToken('example', 'T_NAME');

        self::assertNotSame('', (string) $token);
        self::assertStringContainsString('example', (string) $token);
    }

    #[TestDox('An unknown token is marked as such when printed')]
    public function testUnknownTokenIsMarkedAsSuch(): void
    {
        $printer = self::createPrinter();
        $token = self::createToken('???', null, Channel::Unknown);

        self::assertStringContainsString('unknown', $printer->print($token));
    }
}
