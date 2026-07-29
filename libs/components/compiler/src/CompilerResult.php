<?php

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Exception\GeneratorException;
use Phplrt\Compiler\Generator\GeneratedOutput;
use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\ParserBuilderResult;

/**
 * Represents the result of reading a grammar.
 */
final readonly class CompilerResult implements \Stringable
{
    public function __construct(
        /**
         * Everything the tokens are read by.
         */
        public LexerBuilderResult $lexer,
        /**
         * Everything the tokens are recognized by.
         */
        public ParserBuilderResult $parser,
    ) {}

    /**
     * @throws GeneratorException in case of the result cannot be written down
     */
    public function __toString(): string
    {
        return new GeneratedOutput($this)
            ->__toString();
    }
}
