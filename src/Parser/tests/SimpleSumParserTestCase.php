<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\Token;
use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\ContextInterface;
use Phplrt\Grammar\Concatenation;
use Phplrt\Grammar\Lexeme;
use Phplrt\Grammar\Repetition;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Tests\Stub\AstNode;
use PHPUnit\Framework\ExpectationFailedException;

class SimpleSumParserTestCase extends TestCase implements BuilderInterface
{
    /**
     * @param ContextInterface $context
     * @param array|iterable|NodeInterface|TokenInterface $result
     * @return mixed|void|null
     */
    public function build(ContextInterface $context, $result)
    {
        if (\is_int($context->getState())) {
            return $result;
        }

        $result = \is_array($result) ? $result : [$result];

        return new AstNode($context->getState(), $result);
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws \Throwable
     */
    public function testNodesCount(): void
    {
        $expected = [
            0 => [
                0 => 'sum',
                1 => 3,
            ],
            1 => [
                0 => 'suffix',
                1 => 2,
            ],
            2 => [
                0 => 'suffix',
                1 => 2,
            ],
        ];

        $actual = $this->analyze($this->parseSum('2 + 2 + 4'));

        $this->assertSame($expected, $actual);
    }

    /**
     * @param string $expr
     * @return iterable
     * @throws RuntimeExceptionInterface
     * @throws \Throwable
     */
    private function parseSum(string $expr): iterable
    {
        $lexer = new Lexer([
            'T_WHITESPACE' => '\s+',
            'T_DIGIT'      => '\d+',
            'T_PLUS'       => '\+',
        ], ['T_WHITESPACE']);

        $grammar = [
            0        => new Lexeme('T_DIGIT'),
            1        => new Lexeme('T_PLUS'),
            2        => new Repetition('suffix'),
            'sum'    => new Concatenation([0, 2]),
            'suffix' => new Concatenation([1, 0]),
        ];

        $parser = new Parser($lexer, $grammar, [
            Parser::CONFIG_INITIAL_RULE => 'sum',
            Parser::CONFIG_AST_BUILDER  => $this,
        ]);

        return $parser->parse($expr);
    }

    /**
     * @return void
     * @throws RuntimeExceptionInterface
     * @throws \Throwable
     */
    public function testAstStructure(): void
    {
        $expected = new AstNode('sum', [
            new Token('T_DIGIT', '2', 0),
            new AstNode('suffix', [
                new Token('T_PLUS', '+', 2),
                new Token('T_DIGIT', '2', 4),
            ]),
            new AstNode('suffix', [
                new Token('T_PLUS', '+', 6),
                $last = new Token('T_DIGIT', '4', 8),
            ]),
        ]);

        $last->getBytes();

        $actual = $this->parseSum('2 + 2 + 4');

        $this->assertEquals($expected, $actual);
    }
}
