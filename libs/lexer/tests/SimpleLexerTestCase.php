<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\EndOfInput;

class SimpleLexerTestCase extends TestCase
{
    /**
     * @return void
     * @throws RuntimeExceptionInterface
     */
    public function testDigits(): void
    {
        $expected = $this->tokensOf([
            new Token('T_DIGIT', '23', 0),
            new Token('T_DIGIT', '42', 3),
            new EndOfInput(5),
        ]);

        $lexer = new Lexer(['T_WHITESPACE' => '\s+', 'T_DIGIT' => '\d+'], ['T_WHITESPACE']);

        $this->assertEquals($expected, $this->tokensOf($lexer->lex('23 42')));
    }
}
