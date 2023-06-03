<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Exception\UndefinedToken;
use Phplrt\Position\Position;

class UndefinedTokenTestCase extends TestCase
{
    /**
     * @param string $text
     * @param int $offset
     * @return UndefinedToken
     */
    private function create(string $text = '', int $offset = 0): UndefinedToken
    {
        return new UndefinedToken(Position::fromOffset($text, $offset));
    }

    /**
     * @return void
     */
    public function testName(): void
    {
        $token = $this->create();

        $this->assertSame(TokenInterface::END_OF_INPUT, $token->getName());
    }

    /**
     * @return void
     */
    public function testOffset(): void
    {
        $this->assertSame(0, $this->create()->getOffset());
        $this->assertSame(1, $this->create('1', 1)->getOffset());
    }

    /**
     * @return void
     */
    public function testValue(): void
    {
        $this->assertSame('', $this->create()->getValue());
    }

    /**
     * @return void
     */
    public function testLength(): void
    {
        $this->assertSame(0, $this->create()->getBytes());
    }
}
