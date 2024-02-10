<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Unit;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Exception\UndefinedToken;
use Phplrt\Position\Position;

class UndefinedTokenTest extends TestCase
{
    private function create(string $text = '', int $offset = 0): UndefinedToken
    {
        return new UndefinedToken(Position::fromOffset($text, $offset));
    }

    public function testName(): void
    {
        $token = $this->create();

        $this->assertSame(TokenInterface::END_OF_INPUT, $token->getName());
    }

    public function testOffset(): void
    {
        $this->assertSame(0, $this->create()->getOffset());
        $this->assertSame(1, $this->create('1', 1)->getOffset());
    }

    public function testValue(): void
    {
        $this->assertSame('', $this->create()->getValue());
    }

    public function testLength(): void
    {
        $this->assertSame(0, $this->create()->getBytes());
    }
}
