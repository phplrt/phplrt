<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Exception\RuntimeExceptionInterface;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\LexerCreateInfo;
use Phplrt\Lexer\Token\Token;
use Phplrt\Source\Exception\SourceExceptionInterface;

class LexerTestCase extends TestCase
{
    /**
     * @return void
     * @throws RuntimeExceptionInterface
     * @throws SourceExceptionInterface
     */
    public function testDigits(): void
    {
        $expected = $this->tokensOf([
            Token::new('T_DIGIT', '23'),
            Token::skip('T_WHITESPACE', ' ', 2),
            Token::new('T_DIGIT', '42', 3),
            Token::eoi(5),
        ]);

        $lexer = new Lexer(new LexerCreateInfo(
            tokens: ['T_WHITESPACE' => '\s+', 'T_DIGIT' => '\d+'],
            skip: ['T_WHITESPACE']
        ));

        $this->assertEquals($expected, $this->tokensOf($lexer->lex('23 42')));
    }
}
