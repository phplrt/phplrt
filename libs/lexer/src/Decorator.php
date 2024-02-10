<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\LexerInterface;

abstract class Decorator implements LexerInterface
{
    private LexerInterface $lexer;

    public function __construct(LexerInterface $lexer)
    {
        $this->lexer = $lexer;
    }

    public function lex($source): iterable
    {
        return $this->lexer->lex(...\func_get_args());
    }
}
