<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Tests\Lexer;

use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\EndOfInput;
use PHPUnit\Framework\ExpectationFailedException;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\LexerRuntimeExceptionInterface;

/**
 * Class SimpleLexerTestCase
 */
class SimpleLexerTestCase extends TestCase
{
    /**
     * @return void
     * @throws LexerExceptionInterface
     * @throws LexerRuntimeExceptionInterface
     * @throws ExpectationFailedException
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
