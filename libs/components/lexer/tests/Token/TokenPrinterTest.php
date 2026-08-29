<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Token;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Lexer\Tests\TestCase;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Printer\PrettyTokenPrinter;
use Phplrt\Lexer\Token\Printer\TokenPrinterInterface;
use Phplrt\Lexer\Token\Token;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer')]
#[Test]
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

    public function testTerminalTokenIsPrintedAsEndOfInput(): void
    {
        $printer = self::createPrinter();
        $token = new EndOfInputToken(0);

        Assert::same($printer->print($token), 'end of input');
    }

    public function testPrintedTokenMentionsItsValue(): void
    {
        $printer = self::createPrinter();
        $token = self::createToken('example');

        Assert::string($printer->print($token))->contains('example');
    }

    public function testPrintedTokenMentionsItsName(): void
    {
        $printer = self::createPrinter();
        $token = self::createToken('example', 'T_NAME');

        Assert::string($printer->print($token))->contains('T_NAME');
    }

    public function testPrintedTokenStaysOnASingleLine(): void
    {
        $printer = self::createPrinter();
        $token = self::createToken("first\nsecond");

        Assert::string($printer->print($token))->notContains("\n");
    }

    public function testLongValueIsShortened(): void
    {
        $printer = self::createPrinter();
        $value = \str_repeat('a', 1000);
        $token = self::createToken($value);

        Assert::numeric(\strlen($printer->print($token)))->lessThan(\strlen($value));
    }

    public function testTokenIsConvertibleToString(): void
    {
        $token = self::createToken('example', 'T_NAME');

        Assert::notSame((string) $token, '');
        Assert::string((string) $token)->contains('example');
    }

    public function testUnknownTokenIsMarkedAsSuch(): void
    {
        $printer = self::createPrinter();
        $token = self::createToken('???', null, Channel::Unknown);

        Assert::string($printer->print($token))->contains('unknown');
    }
}
