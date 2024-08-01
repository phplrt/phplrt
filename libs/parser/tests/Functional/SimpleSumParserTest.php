<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Functional;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\Token;
use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Tests\Functional\Stub\AstNode;
use PHPUnit\Framework\ExpectationFailedException;

class SimpleSumParserTest extends TestCase implements BuilderInterface
{
    public function build(Context $context, $result)
    {
        if (\is_int($context->getState())) {
            return $result;
        }

        $result = \is_array($result) ? $result : [$result];

        return new AstNode($context->getState(), $result);
    }

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

    private function parseSum(string $expr): iterable
    {
        $lexer = new Lexer([
            'T_WHITESPACE' => '\s+',
            'T_DIGIT' => '\d+',
            'T_PLUS' => '\+',
        ], ['T_WHITESPACE']);

        $grammar = [
            0 => new Lexeme('T_DIGIT'),
            1 => new Lexeme('T_PLUS'),
            2 => new Repetition('suffix'),
            'sum' => new Concatenation([0, 2]),
            'suffix' => new Concatenation([1, 0]),
        ];

        $parser = new Parser($lexer, $grammar, [
            Parser::CONFIG_INITIAL_RULE => 'sum',
            Parser::CONFIG_AST_BUILDER => $this,
        ]);

        return $parser->parse($expr);
    }

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
