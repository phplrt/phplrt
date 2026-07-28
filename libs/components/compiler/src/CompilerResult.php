<?php

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\ParserBuilderResult;

final readonly class CompilerResult implements CompilerResultInterface
{
    public function __construct(
        public LexerBuilderResult $lexer,
        public ParserBuilderResult $parser,
    ) {}

    public function __toString(): string
    {
        // TODO: Implement __toString() method.
    }
}
