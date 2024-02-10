<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Functional;

use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Token\Token;

class SimpleLexerTest extends TestCase
{
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
