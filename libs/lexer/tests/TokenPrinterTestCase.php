<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Lexer\Printer\Printer;
use Phplrt\Lexer\Printer\PrinterCreateInfo;
use Phplrt\Lexer\Printer\PrinterInterface;
use Phplrt\Lexer\Token\Channel;
use Phplrt\Lexer\Token\Token;

class TokenPrinterTestCase extends TestCase
{
    private PrinterInterface $printer;

    public function setUp(): void
    {
        parent::setUp();

        $this->printer = new Printer(new PrinterCreateInfo(
            length: PrinterCreateInfo::DEFAULT_LENGTH,
            replace: PrinterCreateInfo::DEFAULT_REPLACEMENTS
        ));
    }

    protected function token(string|int $name, string $value, ChannelInterface $channel = Channel::GENERAL)
    {
        return new Token($name, $value, 0, $channel);
    }

    public function testDefaultNamedToken(): void
    {
        $result = $this->printer->print(
            $this->token('T_NAME', 'value')
        );

        $this->assertSame('"value" (T_NAME)', $result);
    }

    public function testDefaultAnonymousToken(): void
    {
        $result = $this->printer->print(
            $this->token(0, 'value')
        );

        $this->assertSame('"value"', $result);
    }

    public function testUnknownNamedToken(): void
    {
        $result = $this->printer->print(
            $this->token('T_NAME', 'value', Channel::UNKNOWN)
        );

        $this->assertSame('"value" (UNKNOWN:T_NAME)', $result);
    }

    public function testUnknownAnonymousToken(): void
    {
        $result = $this->printer->print(
            $this->token(0, 'value', Channel::UNKNOWN)
        );

        $this->assertSame('"value" (UNKNOWN)', $result);
    }

    public function testTokenWithNameEqValue(): void
    {
        $result = $this->printer->print(
            $this->token('A', 'A')
        );

        $this->assertSame('"A"', $result);
    }

    public function testUnknownTokenWithNameEqValue(): void
    {
        $result = $this->printer->print(
            $this->token('A', 'A', Channel::UNKNOWN)
        );

        $this->assertSame('"A" (UNKNOWN)', $result);
    }

    public function testTokenWithLongValue(): void
    {
        $actual = "j¢XuvƒsáZ§Mð7┬TF©-ÞM\x16▓hT*þ╩eAóFÿ\x0E¨Ï┘─{╚\x00\e3º¨:c,am¸.Ô¤╔▒╬ÁÃ½\¬\vÉÍ";
        $expected = 'j\xA2Xuv\u{0192}s\xE1Z\xA7M\xF07\u{252C}TF\xA9-\xDEM\x16\u{2593}hT*\xFE\u{2569}eA\xF3F\xFF';

        $result = $this->printer->print(
            $this->token('T_TEXT', $actual)
        );

        $this->assertSame('"' . $expected . '…" (32+) (T_TEXT)', $result);
    }

    public function testSpecialChars(): void
    {
        $result = $this->printer->print(
            $this->token('T_TEXT', "\t\n\0")
        );

        $this->assertSame('"\t\n\0" (T_TEXT)', $result);
    }
}
