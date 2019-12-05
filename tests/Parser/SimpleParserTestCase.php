<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Tests\Parser;

use Phplrt\Lexer\Lexer;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Rule\Lexeme;
use Phplrt\Parser\Rule\Repetition;
use Phplrt\Parser\Rule\Concatenation;
use PHPUnit\Framework\ExpectationFailedException;
use Phplrt\Contracts\Parser\Exception\ParserRuntimeExceptionInterface;

/**
 * Class SimpleParserTestCase
 */
class SimpleParserTestCase extends TestCase
{
    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws ParserRuntimeExceptionInterface
     * @throws \Throwable
     */
    public function testSumParser(): void
    {
        $lexer = new Lexer([
            'T_WHITESPACE' => '\s+',
            'T_DIGIT'      => '\d+',
            'T_PLUS'       => '\+',
        ], ['T_WHITESPACE']);

        $grammar = [
            0 => new Concatenation([1, 3]),
            1 => new Lexeme('T_DIGIT'),
            2 => new Lexeme('T_PLUS'),
            3 => new Repetition(4),
            4 => new Concatenation([2, 1]),
        ];

        $parser = new Parser($lexer, $grammar);

        $actual = $this->analyze($parser->parse('2 + 2 + 4'));

        $this->assertSame([[0, 0], [2, 0], [4, 0], [6, 0], [8, 0]], $actual);
    }
}
