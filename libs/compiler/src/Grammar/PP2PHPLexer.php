<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Lexer\PositionalLexerInterface;
use Phplrt\Lexer\Token\Composite;

class PP2PHPLexer implements PositionalLexerInterface
{
    public function __construct(private PhpLexer $lexer) {}

    public function lex(mixed $source, int $offset = 0): iterable
    {
        $depth = 0;

        $children = [];
        $value = '';

        foreach ($this->lexer->lex($source, $offset) as $inner) {
            if ($inner->getName() === '{') {
                ++$depth;
            }

            if ($inner->getName() === '}') {
                --$depth;
            }

            if ($depth < 0) {
                break;
            }

            $children[] = $inner;
            $value .= $inner->getValue();
        }

        yield new Composite('T_PHP_CODE', $value, $offset, $children);
    }
}
